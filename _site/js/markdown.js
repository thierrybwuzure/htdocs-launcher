// Rendu Markdown minimal et sûr : tout le texte est échappé avant d'être transformé,
// donc un fichier .md ne peut pas injecter de HTML ni de script dans la page.
// Gère : titres, paragraphes, listes (imbriquées), citations, blocs de code, tableaux,
// séparateurs, gras, italique, barré, code en ligne, liens et images.
(function () {
    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Adresse relative -> adresse du dossier du fichier. Refuse javascript:, data:, vbscript:.
    function safeUrl(url, base) {
        if (/^\s*(javascript|data|vbscript):/i.test(url)) return null;
        if (/^([a-z][a-z0-9+.-]*:|\/|#)/i.test(url)) return url;
        return base + url;
    }

    function inline(text, base) {
        var codes = [];
        var out = escapeHtml(text);

        // Le code en ligne est mis de côté pour ne pas être transformé.
        out = out.replace(/`([^`]+)`/g, function (match, code) {
            codes.push(code);
            return '\u0000' + (codes.length - 1) + '\u0000';
        });

        out = out.replace(/!\[([^\]]*)\]\(([^)\s]+)(?:\s+&quot;[^&]*&quot;)?\)/g, function (match, alt, url) {
            var safe = safeUrl(url, base);
            return safe ? '<img src="' + safe + '" alt="' + alt + '">' : match;
        });
        out = out.replace(/\[([^\]]+)\]\(([^)\s]+)(?:\s+&quot;[^&]*&quot;)?\)/g, function (match, label, url) {
            var safe = safeUrl(url, base);
            return safe ? '<a href="' + safe + '" target="_blank" rel="noopener">' + label + '</a>' : label;
        });

        out = out
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/(^|[^*\w])\*([^*\s][^*]*?)\*(?!\w)/g, '$1<em>$2</em>')
            .replace(/~~([^~]+)~~/g, '<del>$1</del>');

        return out.replace(/\u0000(\d+)\u0000/g, function (match, index) {
            return '<code>' + codes[Number(index)] + '</code>';
        });
    }

    function splitRow(line) {
        return line.trim().replace(/^\||\|$/g, '').split('|').map(function (cell) {
            return cell.trim();
        });
    }

    function render(markdown, base) {
        var lines = markdown.replace(/\r\n?/g, '\n').split('\n');
        var html = '';
        var i = 0;

        while (i < lines.length) {
            var line = lines[i];
            var match;

            // Bloc de code ```
            if ((match = /^```\s*([\w+-]*)/.exec(line))) {
                var code = [];
                i++;
                while (i < lines.length && !/^```/.test(lines[i])) code.push(lines[i++]);
                i++;
                html += '<pre><code>' + escapeHtml(code.join('\n')) + '</code></pre>';
                continue;
            }

            // Titre
            if ((match = /^(#{1,6})\s+(.*?)\s*#*\s*$/.exec(line))) {
                html += '<h' + match[1].length + '>' + inline(match[2], base) + '</h' + match[1].length + '>';
                i++;
                continue;
            }

            // Séparateur
            if (/^\s*([-*_])(\s*\1){2,}\s*$/.test(line)) {
                html += '<hr>';
                i++;
                continue;
            }

            // Citation
            if (/^>\s?/.test(line)) {
                var quote = [];
                while (i < lines.length && /^>\s?/.test(lines[i])) quote.push(lines[i++].replace(/^>\s?/, ''));
                html += '<blockquote>' + render(quote.join('\n'), base) + '</blockquote>';
                continue;
            }

            // Tableau : une ligne d'en-tête suivie d'une ligne |---|---|
            if (line.indexOf('|') !== -1 && i + 1 < lines.length && /^\s*\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?\s*$/.test(lines[i + 1])) {
                var head = splitRow(line);
                i += 2;
                html += '<table><thead><tr>' + head.map(function (cell) { return '<th>' + inline(cell, base) + '</th>'; }).join('') + '</tr></thead><tbody>';
                while (i < lines.length && lines[i].indexOf('|') !== -1 && lines[i].trim() !== '') {
                    html += '<tr>' + splitRow(lines[i++]).map(function (cell) { return '<td>' + inline(cell, base) + '</td>'; }).join('') + '</tr>';
                }
                html += '</tbody></table>';
                continue;
            }

            // Liste (à puces ou numérotée), avec imbrication par indentation
            if (/^\s*([-*+]|\d+\.)\s+/.test(line)) {
                var stack = [];
                while (i < lines.length && (match = /^(\s*)([-*+]|\d+\.)\s+(.*)$/.exec(lines[i]))) {
                    var depth = Math.floor(match[1].replace(/\t/g, '    ').length / 2);
                    var tag = /\d/.test(match[2]) ? 'ol' : 'ul';
                    while (stack.length > depth + 1) html += '</li></' + stack.pop() + '>';
                    if (stack.length === depth + 1) {
                        html += '</li><li>';
                    } else {
                        while (stack.length < depth + 1) {
                            html += '<' + tag + '><li>';
                            stack.push(tag);
                        }
                    }
                    html += inline(match[3], base);
                    i++;
                }
                while (stack.length) html += '</li></' + stack.pop() + '>';
                continue;
            }

            // Ligne vide
            if (/^\s*$/.test(line)) {
                i++;
                continue;
            }

            // Paragraphe : lignes consécutives jusqu'à une ligne vide ou un autre bloc
            var para = [];
            while (i < lines.length && !/^\s*$/.test(lines[i]) && !/^(#{1,6}\s|```|>\s?|\s*([-*+]|\d+\.)\s+)/.test(lines[i])) {
                para.push(lines[i++]);
            }
            if (!para.length) {
                para.push(lines[i++]);
            }
            html += '<p>' + inline(para.join('\n'), base).replace(/\n/g, '<br>') + '</p>';
        }

        return html;
    }

    window.renderMarkdown = render;
})();
