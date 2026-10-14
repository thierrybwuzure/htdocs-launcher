-- Exporte un document en PDF avec Microsoft Word.
-- Arguments : chemin du document, chemin du PDF à créer. Referme le document, et quitte l'application si elle n'était pas ouverte avant.
on run argv
	set inPath to item 1 of argv
	set outPath to item 2 of argv
	tell application "System Events" to set wasRunning to (name of processes) contains "Microsoft Word"
	-- Sécurité : si ce document est déjà ouvert, on s'arrête. Le refermer "sans enregistrer" ferait perdre des modifications.
	set AppleScript's text item delimiters to "/"
	set fileName to last text item of inPath
	set AppleScript's text item delimiters to ""
	if wasRunning then
		tell application "Microsoft Word"
			set openNames to name of every document
		end tell
		if openNames contains fileName then
			error "Ce document est déjà ouvert dans Microsoft Word : ferme-le d'abord (enregistre-le si besoin), puis relance l'export." number 1001
		end if
	end if
	try
		tell application "Microsoft Word"
			open POSIX file inPath
			set theDoc to active document
			save as theDoc file name outPath file format format PDF
			close theDoc saving no
		end tell
	on error errMsg number errNum
		if not wasRunning then
			try
				tell application "Microsoft Word" to quit saving no
			end try
		end if
		error errMsg number errNum
	end try
	if not wasRunning then
		tell application "Microsoft Word" to quit saving no
	end if
end run
