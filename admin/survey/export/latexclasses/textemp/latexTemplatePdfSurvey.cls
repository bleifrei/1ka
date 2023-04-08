\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{latexTemplatePdfSurvey}
\LoadClass{article}
%uporabljene knjiznice
\usepackage[a4paper, portrait, left=20mm, right=20mm, headheight=1cm, top=1cm, bottom=2cm, footskip=3cm,  includehead, includefoot, heightrounded]{geometry} % za robove, ipd.
\usepackage{tabularx, booktabs} % za lazje urejanje in izris tabel (ni podprto v latex2rtf)
\usepackage{ltablex} % Mod­i­fies the tab­u­larx en­vi­ron­ment to com­bine the fea­tures of the tab­u­larx pack­age (auto-sized columns in a fixed width ta­ble) with those of the longtable pack­age (multi-page ta­bles)
	%\usepackage{color}
\usepackage[dvipsnames]{xcolor}	% za uporabo HTML in RGB barv
\usepackage{pgf}		% za aritmetiko z length
\usepackage{printlen}		% 
\usepackage{enumitem}	% za itemize, ki je potreben za pravilen izris rolet in izberite iz seznama v tabelah
\usepackage{eurosym}	% za izpis simbola €
\usepackage[export]{adjustbox}	% za poravnavo slik
\usepackage{wasysym}	% za izris radio button, checkbox

\usepackage{tikzsymbols}	%za izpis emoji-jev

\usepackage{textgreek}	%za resevanje grskih crk

\usepackage{tipa} %za cudne simbole
%\usepackage[T2A,T1]{fontenc} %za cirilico
\usepackage[LGR,T3,T2A,T1]{fontenc} %za cirilico
%\usepackage[russian,english]{babel} %za cirilico
\usepackage[greek,russian,english]{babel} %za cirilico in grsko
%\usepackage[russian]{babel} %za cirilico

\usepackage[utf8]{inputenc} % za uporabo utf8
\usepackage{amssymb}	%za unicode simbole

%DEFINIRANJE NADOMESTIL NEIZPISLJIVIH UNICODE CHARACTER-JEV
\DeclareUnicodeCharacter{200B}{{\hskip 0pt}}
\DeclareUnicodeCharacter{03C7}{$\chi$}
\DeclareUnicodeCharacter{2265}{$\geq$}
\DeclareUnicodeCharacter{03B5}{$\epsilon$}
\DeclareUnicodeCharacter{2003}{$\enspace$}
\DeclareUnicodeCharacter{0014}{$\thinspace$}	%ni ta pravi nadomestek, ker v Latexu tak simbol ne obstaja

%Emoji - izpis urejen s slikicami emoji-jev
\DeclareUnicodeCharacter{1F600}{\includegraphics[height=1em]{1F600.png}}
\DeclareUnicodeCharacter{1F601}{\includegraphics[height=1em]{1F601.png}}
\DeclareUnicodeCharacter{1F602}{\includegraphics[height=1em]{1F602.png}}
\DeclareUnicodeCharacter{1F603}{\includegraphics[height=1em]{1F603.png}}
\DeclareUnicodeCharacter{1F604}{\includegraphics[height=1em]{1F604.png}}
\DeclareUnicodeCharacter{1F605}{\includegraphics[height=1em]{1F605.png}}
\DeclareUnicodeCharacter{1F606}{\includegraphics[height=1em]{1F606.png}}
\DeclareUnicodeCharacter{1F609}{\includegraphics[height=1em]{1F609.png}}
\DeclareUnicodeCharacter{1F60A}{\includegraphics[height=1em]{1F60A.png}}
\DeclareUnicodeCharacter{1F60B}{\includegraphics[height=1em]{1F60B.png}}
\DeclareUnicodeCharacter{1F60E}{\includegraphics[height=1em]{1F60C.png}}
\DeclareUnicodeCharacter{1F60D}{\includegraphics[height=1em]{1F60D.png}}
\DeclareUnicodeCharacter{1F60E}{\includegraphics[height=1em]{1F60E.png}}
\DeclareUnicodeCharacter{1F60D}{\includegraphics[height=1em]{1F60F.png}}

\DeclareUnicodeCharacter{1F600}{\includegraphics[height=1em]{1F610.png}}
\DeclareUnicodeCharacter{1F611}{\includegraphics[height=1em]{1F611.png}}
\DeclareUnicodeCharacter{1F612}{\includegraphics[height=1em]{1F612.png}}
\DeclareUnicodeCharacter{1F613}{\includegraphics[height=1em]{1F613.png}}
\DeclareUnicodeCharacter{1F614}{\includegraphics[height=1em]{1F614.png}}
\DeclareUnicodeCharacter{1F615}{\includegraphics[height=1em]{1F615.png}}
\DeclareUnicodeCharacter{1F616}{\includegraphics[height=1em]{1F616.png}}
\DeclareUnicodeCharacter{1F619}{\includegraphics[height=1em]{1F619.png}}
\DeclareUnicodeCharacter{1F61A}{\includegraphics[height=1em]{1F61A.png}}
\DeclareUnicodeCharacter{1F61B}{\includegraphics[height=1em]{1F61B.png}}
\DeclareUnicodeCharacter{1F61E}{\includegraphics[height=1em]{1F61C.png}}
\DeclareUnicodeCharacter{1F61D}{\includegraphics[height=1em]{1F61D.png}}
\DeclareUnicodeCharacter{1F61E}{\includegraphics[height=1em]{1F61E.png}}
\DeclareUnicodeCharacter{1F61D}{\includegraphics[height=1em]{1F61F.png}}

\DeclareUnicodeCharacter{1F600}{\includegraphics[height=1em]{1F620.png}}
\DeclareUnicodeCharacter{1F621}{\includegraphics[height=1em]{1F621.png}}
\DeclareUnicodeCharacter{1F622}{\includegraphics[height=1em]{1F622.png}}
\DeclareUnicodeCharacter{1F623}{\includegraphics[height=1em]{1F623.png}}
\DeclareUnicodeCharacter{1F624}{\includegraphics[height=1em]{1F624.png}}
\DeclareUnicodeCharacter{1F625}{\includegraphics[height=1em]{1F625.png}}
\DeclareUnicodeCharacter{1F626}{\includegraphics[height=1em]{1F626.png}}
\DeclareUnicodeCharacter{1F629}{\includegraphics[height=1em]{1F629.png}}
\DeclareUnicodeCharacter{1F62A}{\includegraphics[height=1em]{1F62A.png}}
\DeclareUnicodeCharacter{1F62B}{\includegraphics[height=1em]{1F62B.png}}
\DeclareUnicodeCharacter{1F62E}{\includegraphics[height=1em]{1F62C.png}}
\DeclareUnicodeCharacter{1F62D}{\includegraphics[height=1em]{1F62D.png}}
\DeclareUnicodeCharacter{1F62E}{\includegraphics[height=1em]{1F62E.png}}
\DeclareUnicodeCharacter{1F62D}{\includegraphics[height=1em]{1F62F.png}}

\DeclareUnicodeCharacter{1F600}{\includegraphics[height=1em]{1F630.png}}
\DeclareUnicodeCharacter{1F631}{\includegraphics[height=1em]{1F631.png}}
\DeclareUnicodeCharacter{1F632}{\includegraphics[height=1em]{1F632.png}}
\DeclareUnicodeCharacter{1F633}{\includegraphics[height=1em]{1F633.png}}
\DeclareUnicodeCharacter{1F634}{\includegraphics[height=1em]{1F634.png}}
\DeclareUnicodeCharacter{1F635}{\includegraphics[height=1em]{1F635.png}}
\DeclareUnicodeCharacter{1F636}{\includegraphics[height=1em]{1F636.png}}
\DeclareUnicodeCharacter{1F639}{\includegraphics[height=1em]{1F639.png}}
\DeclareUnicodeCharacter{1F63A}{\includegraphics[height=1em]{1F63A.png}}
\DeclareUnicodeCharacter{1F63B}{\includegraphics[height=1em]{1F63B.png}}
\DeclareUnicodeCharacter{1F63E}{\includegraphics[height=1em]{1F63C.png}}
\DeclareUnicodeCharacter{1F63D}{\includegraphics[height=1em]{1F63D.png}}
\DeclareUnicodeCharacter{1F63E}{\includegraphics[height=1em]{1F63E.png}}
\DeclareUnicodeCharacter{1F63D}{\includegraphics[height=1em]{1F63F.png}}

\DeclareUnicodeCharacter{1F600}{\includegraphics[height=1em]{1F640.png}}
\DeclareUnicodeCharacter{1F641}{\includegraphics[height=1em]{1F641.png}}
\DeclareUnicodeCharacter{1F642}{\includegraphics[height=1em]{1F642.png}}
\DeclareUnicodeCharacter{1F643}{\includegraphics[height=1em]{1F643.png}}
\DeclareUnicodeCharacter{1F644}{\includegraphics[height=1em]{1F644.png}}
\DeclareUnicodeCharacter{1F645}{\includegraphics[height=1em]{1F645.png}}
\DeclareUnicodeCharacter{1F646}{\includegraphics[height=1em]{1F646.png}}
\DeclareUnicodeCharacter{1F649}{\includegraphics[height=1em]{1F649.png}}
\DeclareUnicodeCharacter{1F64A}{\includegraphics[height=1em]{1F64A.png}}
\DeclareUnicodeCharacter{1F64B}{\includegraphics[height=1em]{1F64B.png}}
\DeclareUnicodeCharacter{1F64E}{\includegraphics[height=1em]{1F64C.png}}
\DeclareUnicodeCharacter{1F64D}{\includegraphics[height=1em]{1F64D.png}}
\DeclareUnicodeCharacter{1F64E}{\includegraphics[height=1em]{1F64E.png}}
\DeclareUnicodeCharacter{1F64D}{\includegraphics[height=1em]{1F64F.png}}

%Emoji - izpis urejen s slikicami emoji-jev - konec

%DEFINIRANJE NADOMESTIL NEIZPISLJIVIH UNICODE CHARACTER-JEV - KONEC

\usepackage{graphicx}	% za prikazovanje slik in ostalih grafik
\usepackage{colortbl}		%The pack­age al­lows rows and columns to be coloured, and even in­di­vid­ual cells
\usepackage{fancyhdr}	% za ureditev glav in nog
\usepackage{seqsplit} % za samodejno razbijanje dolgih besed v tabelah
\usepackage{url} %za razbijanje url-jev v tabelah
\urlstyle{same} %nastavitev, da je font URL isti kot je font ostalega besedila (http://ctan.ijs.si/tex-archive/macros/latex/contrib/url/url.pdf)
\usepackage{montserrat}	% za uporabo Montserrat pisave v dokumentu
\usepackage{multirow}	% za spajanje vrstic v tabeli
\usepackage[hidelinks]{hyperref}
\usepackage{tikz} % za risanje drsnikov
\usetikzlibrary{calc} % za risanje drsnikov
%definiranje uporabljenih barv
\definecolor{Mycolor1}{HTML}{00F9DE}
\definecolor{oneclick}{rgb}{0.2, 0.2, 0.2}
\definecolor{besedilo}{HTML}{535050}
\definecolor{crta}{HTML}{1e88e5}
\definecolor{1ka_orange}{HTML}{ffa608}
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
%definiranje spremenljivk (dolzin, ...)
\newlength{\questionTotalLength}
\newlength{\answerLength}
\newlength{\questionLength}
\newlength{\opombaLength}
\newcounter{opomba}
%definiranje novih ukazov, funkcij, nastavitev potrebnih za izris dolocenih delov besedila
\newcommand{\radio}{\Large \ooalign{\hidewidth$\bullet$\hidewidth\cr$\ocircle$}}	%za izris radio button
\linespread{1.5}	% za razmik med vrsticami
%\fontencoding{T1}\selectfont % za encode besedila
%\fontencoding{T2A,T1}\selectfont % za encode besedila
\renewcommand{\familydefault}{\sfdefault}	% za izbiro novega fonta Montserrat
\color{besedilo} % za izbiro barve besedila
\newenvironment{absolutelynopagebreak}	% za ureditev dela besedila, kjer ne sme biti odsek strani; za prepreciti prelome strani, kjer ni potrebno
  {\par\nobreak\vfil\penalty0\vfilneg
   \vtop\bgroup}
  {\par\xdef\tpd{\the\prevdepth}\egroup
   \prevdepth=\tpd}
\newenvironment{survey}		% za ureditev prostora za izpis vprasalnika
 {\parindent0pt \fontsize{10}{12} \selectfont }
 { }
 \newenvironment{gdpr}		% za ureditev prostora za izpis gdpr
 %{\parindent0pt \fontsize{10}{12} \selectfont }
 { }
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
%newcommand{\headerfooter}[5]{ %funkcija za izpis glave in noge
%	\lhead{\begin{tabular}{@{} m{135mm} } #1 \end{tabular}}
%	\rhead{\includegraphics[#4]{#2}}
%	\fancyfoot[l]{www.1ka.si} 
%	%\fancyfoot[r]{#3 \thepage{}}
%	\fancyfoot[r]{ \thepage}
%}
\newcommand{\headerfooter}[6]{ %funkcija za izpis glave in noge
	\lhead{\begin{tabular}{@{} m{135mm} } #1 #6 \end{tabular}}
	\rhead{\includegraphics[#4]{#2}}
	\fancyfoot[l]{www.1ka.si} 
	%\fancyfoot[r]{#3 \thepage{}}
	\fancyfoot[r]{ \thepage}
}
%vse za glavo in nogo - konec
%vse za naslovnico
\newcommand{\naslovnica}[9]{
	\begin{titlepage}
		\linespread{1}
		\includegraphics[#1]{#2}
		\vfill				
		\noindent
		\huge \fontseries{l}\selectfont {#3}\par
		\vspace{0.5cm}				
		\noindent
		\MakeUppercase{{\huge \textbf{\noindent {#4} }}} \par				
		\noindent				
		\textcolor{crta}{\noindent\makebox[\linewidth]{\rule{\textwidth}{6pt}}} \par					
		\noindent
		{\large \fontseries{l} \selectfont				
			\begin{tabularx}{\textwidth}{Xl}				
				& \\
				#5 \\
				& \\
				#6 \\
				& \\
				#7 \\
				& \\
				#8 \\
				& \\
				#9
			\end{tabularx}
		}
		\vfill
	\end{titlepage}
}
%vse za naslovnico - konec