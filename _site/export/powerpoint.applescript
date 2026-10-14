-- Exporte une présentation en PDF avec Microsoft PowerPoint.
-- Arguments : chemin du document, chemin du PDF à créer. Referme le document, et quitte l'application si elle n'était pas ouverte avant.
on run argv
	set inPath to item 1 of argv
	set outPath to item 2 of argv
	tell application "System Events" to set wasRunning to (name of processes) contains "Microsoft PowerPoint"
	-- Sécurité : si ce présentation est déjà ouvert, on s'arrête. Le refermer "sans enregistrer" ferait perdre des modifications.
	set AppleScript's text item delimiters to "/"
	set fileName to last text item of inPath
	set AppleScript's text item delimiters to ""
	if wasRunning then
		tell application "Microsoft PowerPoint"
			set openNames to name of every presentation
		end tell
		if openNames contains fileName then
			error "Ce présentation est déjà ouvert dans Microsoft PowerPoint : ferme-le d'abord (enregistre-le si besoin), puis relance l'export." number 1001
		end if
	end if
	try
		tell application "Microsoft PowerPoint"
			open POSIX file inPath
			set pres to active presentation
			save pres in (POSIX file outPath) as save as PDF
			close pres saving no
		end tell
	on error errMsg number errNum
		if not wasRunning then
			try
				tell application "Microsoft PowerPoint" to quit saving no
			end try
		end if
		error errMsg number errNum
	end try
	if not wasRunning then
		tell application "Microsoft PowerPoint" to quit saving no
	end if
end run
