\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{latexTemplatePdfAnalysisPortrait}
\LoadClass[10pt]{article}
%uporabljene knjiznice
%\usepackage[a4paper, portrait, left=20mm, right=20mm, headheight=1cm, top=1cm, bottom=2cm, footskip=3cm,  includehead, includefoot, heightrounded]{geometry} % za robove, ipd.
\usepackage[a4paper, portrait, left=15mm, right=15mm, headheight=1cm, top=1cm, bottom=2cm, footskip=3cm,  includehead, includefoot, heightrounded]{geometry} % za robove, ipd.
\usepackage{tabularx, booktabs} % za lazje urejanje in izris tabel (ni podprto v latex2rtf)
\usepackage{ltablex} % Mod­i­fies the tab­u­larx en­vi­ron­ment to com­bine the fea­tures of the tab­u­larx pack­age (auto-sized columns in a fixed width ta­ble) with those of the longtable pack­age (multi-page ta­bles)
\usepackage{color}
\usepackage[dvipsnames]{xcolor}	% za uporabo HTML in RGB barv
\usepackage{pgf}		% za aritmetiko z length
\usepackage{printlen}		% 
\usepackage{eurosym}	% za izpis simbola €
\usepackage[export]{adjustbox}	% za poravnavo slik
\usepackage[utf8]{inputenc} % za uporabo utf8
\usepackage{amssymb}	%za unicode simbole
\usepackage{graphicx}	% za prikazovanje slik in ostalih grafik
\usepackage{colortbl}		%The pack­age al­lows rows and columns to be coloured, and even in­di­vid­ual cells
\usepackage{fancyhdr}	% za ureditev glav in nog
\usepackage{montserrat}	% za uporabo Montserrat pisave v dokumentu
\usepackage{multirow}	% za spajanje vrstic v tabeli
\usepackage{hyperref}
\usepackage{tikz} % za risanje
\usepackage{seqsplit} % za samodejno razbijanje dolgih besed v tabelah
\usepackage{url} %za razbijanje url-jev v tabelah
\usepackage{textgreek}	%za resevanje grskih crk
\urlstyle{same} %nastavitev, da je font URL isti kot je font ostalega besedila (http://ctan.ijs.si/tex-archive/macros/latex/contrib/url/url.pdf)
%\usepackage{adjustbox} %za samodejno dimenzioniranje tabel
%definiranje uporabljenih barv
\definecolor{besedilo}{HTML}{535050}
\definecolor{crta}{HTML}{1e88e5}
\definecolor{crtaGraf}{HTML}{ffa608}
\definecolor{komentar}{HTML}{ff0000}
%definiranje poravnav za tabele
\newcolumntype{C}{>{\centering\arraybackslash}X} % za sredinsko poravnavo celice, ki se samodejno prilagaja sirini
\newcolumntype{R}{>{\raggedleft\arraybackslash}X} % za desno poravnavo celice, ki se samodejno prilagaja sirini
\renewcommand{\tabularxcolumn}[1]{>{\arraybackslash}m{#1}} % za sredinsko poravnane zadeve v tabularx tabeli
\newcolumntype{s}{>{\hsize=.55\hsize \centering\arraybackslash}X} % za sredinsko poravnane celice manjse dimenzije (0.55 navadne dimenzije)
\newcolumntype{S}{>{\hsize=.2\hsize}X} % za celice manjse dimenzije (0.3 navadne dimenzije)
\newcolumntype{b}{>{\hsize=.5\hsize}X} % za celice manjse dimenzije (0.5 navadne dimenzije)
\newcolumntype{B}{>{\hsize=1.3\hsize}X} % za celico vecje dimenzije od navadne (1.3 navadne dimenzije)
\newcolumntype{P}{>{\hsize=.1\hsize \centering\arraybackslash}X} % za fiksno 10% fiksno sirino stolpca, ki ima sredinsko poravnavo (npr. analiza prvi, levi stolpec)
\newcolumntype{A}{>{\hsize=0.17\textwidth}X} % za fiksno sirino prvega stolpca@tabularx
%\newcolumntype{A}{>{\hsize=0.1\textwidth}X} % za fiksno sirino prvega stolpca@tabularx
%definiranje spremenljivk (dolzin, ...)
\newlength{\questionTotalLength}
\newlength{\answerLength}
\newlength{\questionLength}
\newlength{\opombaLength}
\newcounter{opomba}
%definiranje novih ukazov, funkcij, nastavitev potrebnih za izris dolocenih delov besedila
%\linespread{1.5}	% za razmik med vrsticami
\linespread{1.2}	% za razmik med vrsticami
\fontencoding{T1}\selectfont % za encode besedila
\renewcommand{\familydefault}{\sfdefault}	% za izbiro novega fonta Montserrat
\renewcommand{\arraystretch}{1.5}
\color{besedilo} % za izbiro barve besedila
\newenvironment{analysis} % za ureditev prostora za izpis analize
 {\parindent0pt \fontsize{6}{6} \selectfont}
 { }
\newenvironment{tableAnalysis} % za ureditev prostora za izpis tabele v analizah 
 {\parindent0pt \fontsize{6}{6} \selectfont}
 { }
\newenvironment{tableStatus} % za ureditev prostora za izpis tabele v status
 %{\parindent0pt \fontsize{8}{12} \selectfont }
 {\parindent0pt \fontsize{8}{9} \selectfont }
 { }
\newenvironment{other} % za ureditev prostora za izpis tabele v status
 %{\parindent0pt \fontsize{8}{12} \selectfont }
 {\parindent0pt \fontsize{8}{9} \selectfont }
 { }
\newcommand{\forceindent}{\leavevmode{\parindent=1em\indent}} % 
\pdfcompresslevel=0	%uncompressed PDFs za pohitritev
\pdfobjcompresslevel=0 %uncompressed PDFs za pohitritev
%vse za glavo in nogo
\pagestyle{fancy}
\fancyhf{}
\setlength{\headheight}{41pt} %Make it at least 40.35403pt.
\renewcommand{\headrule}{\hbox to\headwidth{\color{crta}\leaders\hrule height \headrulewidth\hfill}}	%ureditev druge barve za crto glave
\renewcommand{\headrulewidth}{6pt}	%debelina crte glave
\renewcommand{\footrulewidth}{0.25pt}	%debelina crte noga
\setlength{\headsep}{1.5cm}	%odmik iz roba strani
\newcommand{\headerfooter}[5]{ %funkcija za izpis glave in noge
	\lhead{\begin{tabular}{@{} m{135mm} } #1 \end{tabular}}
	\rhead{\includegraphics[#4]{#2}}
	\fancyfoot[l]{www.1ka.si} 
	\fancyfoot[r]{\thepage}
}
%vse za glavo in nogo - konec

\DeclareUnicodeCharacter{2212}{-}
\DeclareUnicodeCharacter{030C}{Č}
\DeclareUnicodeCharacter{200B}{{\hskip 0pt}}
\DeclareUnicodeCharacter{03C7}{$\chi$}
\DeclareUnicodeCharacter{2265}{$\geq$}
\DeclareUnicodeCharacter{03B5}{$\epsilon$}
\DeclareUnicodeCharacter{2003}{$\enspace$}
\DeclareUnicodeCharacter{0014}{$\thinspace$}	%ni ta pravi nadomestek, ker v Latexu tak simbol ne obstaja