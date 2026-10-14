#!/bin/bash
# Exporte un document Office (pptx, docx, xlsx...) en PDF avec l'application Microsoft correspondante,
# puis range le PDF dans le cache de la page d'aperçu (http://localhost/_preview.php).
#
# A lancer depuis le Terminal, pas depuis Apache : macOS interdit au serveur web de lire le PDF
# que ces applications ecrivent dans leur dossier protege. Ton Terminal, lui, a le droit.
#
# Usage : bash export-pdf.sh "/chemin/complet/du/document.pptx"
#         (le bouton « Exporter en PDF » de la page d'apercu affiche la commande toute prete)
#
# L'application s'ouvre brievement. macOS peut demander une autorisation la premiere fois : accepte-la.

set -u

TIMEOUT=120
CACHE="$HOME/Library/Caches/htdocs-preview"
HERE="$(cd "$(dirname "$0")" && pwd)"

DOC="${1:-}"
if [ -z "$DOC" ] || [ ! -f "$DOC" ]; then
  echo "Usage : bash export-pdf.sh \"/chemin/complet/du/document.pptx\""; exit 1
fi

# Application, script AppleScript et conteneur selon l'extension
ext=$(printf '%s' "${DOC##*.}" | tr 'A-Z' 'a-z')
case "$ext" in
  pptx|ppt)      SCRIPT=powerpoint; BOX_ID=com.microsoft.Powerpoint ;;
  docx|doc|odt)  SCRIPT=word;       BOX_ID=com.microsoft.Word ;;
  xlsx|xls)      SCRIPT=excel;      BOX_ID=com.microsoft.Excel ;;
  *) echo "Type de document non pris en charge : .$ext"; exit 1 ;;
esac

# Meme identifiant de cache que la page d'apercu : l'empreinte du contenu du fichier
key=$(shasum "$DOC" | cut -d' ' -f1)
target="$CACHE/$key.pdf"
mkdir -p "$CACHE"

if [ -s "$target" ]; then
  echo "Ce document est deja exporte : $target"; exit 0
fi

# Le PDF est cree dans le dossier de l'application (seul endroit ou elle peut ecrire sans autorisation)
BOX="$HOME/Library/Containers/$BOX_ID/Data/tmp"
mkdir -p "$BOX"
tmp="$BOX/htdocs-export-$$.pdf"
rm -f -- "${tmp:?}"

echo "Export de « $(basename "$DOC") » avec $SCRIPT (jusqu'a ${TIMEOUT} s)..."
osascript "$HERE/$SCRIPT.applescript" "$DOC" "$tmp" > /tmp/htdocs-export.log 2>&1 &
pid=$!
for i in $(seq 1 $TIMEOUT); do
  kill -0 $pid 2>/dev/null || break
  sleep 1
done
if kill -0 $pid 2>/dev/null; then
  kill $pid 2>/dev/null
  echo "ERREUR : l'application n'a pas repondu en ${TIMEOUT} s."
  echo "Elle est peut-etre restee ouverte avec le document : ferme-le (sans enregistrer)."
  exit 1
fi

if [ ! -s "$tmp" ]; then
  echo "ERREUR : aucun PDF n'a ete cree."
  grep -v '^sandbox_extension_issue_file' /tmp/htdocs-export.log | head -5
  if grep -q -- '-1743' /tmp/htdocs-export.log; then
    echo "macOS a refuse l'acces : Reglages Systeme > Confidentialite et securite > Automatisation, puis autorise le Terminal."
  fi
  exit 1
fi

# Copie du PDF vers le cache. macOS refuse parfois la lecture juste apres l'export : on reessaie quelques fois.
copied=no
for i in 1 2 3 4 5 6; do
  if cp "$tmp" "$target" 2>/dev/null && [ "$(wc -c < "$target" | tr -d ' ')" = "$(wc -c < "$tmp" | tr -d ' ')" ]; then
    copied=yes; break
  fi
  rm -f -- "${target:?}"
  sleep 2
done

if [ "$copied" = yes ]; then
  rm -f -- "${tmp:?}"
  echo "OK : PDF pret ($(wc -c < "$target" | tr -d ' ') octets)."
  echo "Recharge la page d'apercu du document dans le navigateur."
else
  echo "ERREUR : le PDF a ete cree mais macOS refuse de le copier vers le cache."
  echo "Il est ici : $tmp"
  echo "Tu peux l'ouvrir avec : open -R \"$tmp\""
  echo "Pour le ranger quand meme dans le cache : cp -- \"$tmp\" \"$target\""
  echo "Si ce message revient : ce Terminal n'a pas le droit de lire les donnees de l'application (Reglages Systeme > Confidentialite et securite)."
  exit 1
fi
