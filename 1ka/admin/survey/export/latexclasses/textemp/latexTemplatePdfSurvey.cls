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

\usepackage{textgreek}	%za resevanje grskih crk

\usepackage[T2A,T1]{fontenc} %za cirilico
\usepackage[russian,english]{babel} %za cirilico
%\usepackage[russian]{babel} %za cirilico

\usepackage[utf8]{inputenc} % za uporabo utf8
\usepackage{amssymb}	%za unicode simbole
\DeclareUnicodeCharacter{200B}{{\hskip 0pt}}
\DeclareUnicodeCharacter{03C7}{$\chi$}
\DeclareUnicodeCharacter{2265}{$\geq$}
\DeclareUnicodeCharacter{03B5}{$\epsilon$}
\DeclareUnicodeCharacter{2003}{$\enspace$}
\DeclareUnicodeCharacter{0014}{$\thinspace$}	%ni ta pravi nadomestek, ker v Latexu tak simbol ne obstaja

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