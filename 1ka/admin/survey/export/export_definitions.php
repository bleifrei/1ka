<?php

# ali je OS windows ali linux
define('IS_WINDOWS', (DIRECTORY_SEPARATOR === '\\') ? TRUE : FALSE);
define('IS_LINUX', (DIRECTORY_SEPARATOR === '\\') ? FALSE : TRUE);


define("M_ANALIZA_DESCRIPTOR", "descriptor");
define("M_ANALIZA_FREQUENCY", "frequency");
define("M_ANALIZA_CROSSTAB", "crosstabs");
define("M_ANALIZA_STATISTICS", "statistics");
define("M_ANALIZA_SUMS", "sums");


/*PDF*/
define("A_REPORT_VPRASALNIK_PDF", "vprasalnik_pdf");
define("A_REPORT_PDF_RESULTS","pdf_results");
define("A_REPORT_PDF_COMMENT","pdf_comment");

define("M_REPORT_ANALIZA_PDF_FREKVENCA","frequency");
define("M_REPORT_ANALIZA_PDF_CROSSTAB_IZPIS","crosstabs_izpis");
define("M_REPORT_ANALIZA_PDF_MULTICROSSTAB_IZPIS","multicrosstabs_izpis");
define("M_REPORT_ANALIZA_PDF_MEAN_IZPIS","mean_izpis");
define("M_REPORT_ANALIZA_PDF_TTEST_IZPIS","ttest_izpis");
define("M_REPORT_ANALIZA_PDF_BREAK_IZPIS","break_izpis");
define("M_REPORT_ANALIZA_PDF_STAT","statistics");
define("M_REPORT_ANALIZA_PDF_CHARTS","charts");
define("M_REPORT_ANALIZA_PDF_SUMS","sums");
define("M_REPORT_ANALIZA_PDF_CREPORT","creport_pdf");

define("A_REPORT_PDF_STATUS","status");
define("A_REPORT_PDF_EDITS_ANALYSIS","editsAnalysis");
define("A_REPORT_PDF_LIST","list_pdf");
define("M_REPORT_PDF_EVOLI","pdf_evoli");
define("M_REPORT_PDF_TEAMMETER","pdf_teammeter");
define("M_REPORT_PDF_MFDPS","pdf_mfpds");
define("M_REPORT_PDF_HEATMAP_IMAGE","heatmap_image_pdf");

define("M_REPORT_HIERARHIJA_PDF_IZPIS", "hierarhija_pdf_izpis");

define("A_GDPR_PDF_INDIVIDUAL", "pdf_gdpr_individual");
define("A_GDPR_PDF_ACTIVITY", "pdf_gdpr_activity");


/*RTF*/
define("A_REPORT_VPRASALNIK_RTF", "vprasalnik_rtf");
define("A_REPORT_RTF_RESULTS", "rtf_results");
define("A_REPORT_RTF_COMMENT", "rtf_comment");

define("M_REPORT_ANALIZA_RTF_FREKVENCA", "frequency_rtf");
define("M_REPORT_ANALIZA_RTF_CROSSTAB_IZPIS", "crosstabs_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_MULTICROSSTAB_IZPIS", "multicrosstabs_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_MEAN_IZPIS", "mean_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_TTEST_IZPIS", "ttest_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_BREAK_IZPIS", "break_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_STAT", "statistics_rtf");
define("M_REPORT_ANALIZA_RTF_SUMS", "sums_rtf");
define("M_REPORT_ANALIZA_RTF_CHARTS", "charts_rtf");
define("M_REPORT_ANALIZA_RTF_CREPORT", "creport_rtf");

define("A_REPORT_RTF_LIST", "list_rtf");
define("M_REPORT_ANALIZA_RTF_HEATMAP_IMAGE","heatmap_image_rtf");

define("A_GDPR_RTF_INDIVIDUAL", "rtf_gdpr_individual");
define("A_GDPR_RTF_ACTIVITY", "rtf_gdpr_activity");


/*XLS*/
define("M_REPORT_ANALIZA_XLS_STAT", "statistics_xls");
define("M_REPORT_ANALIZA_XLS_FREKVENCA", "frequency_xls");
define("M_REPORT_ANALIZA_XLS_CROSSTAB_IZPIS", "crosstabs_izpis_xls");
define("M_REPORT_ANALIZA_XLS_MULTICROSSTAB_IZPIS", "multicrosstabs_izpis_xls");
define("M_REPORT_ANALIZA_XLS_SUMS", "sums_xls");
define("M_REPORT_ANALIZA_XLS_MEAN_IZPIS", "mean_izpis_xls");
define("M_REPORT_ANALIZA_XLS_TTEST_IZPIS", "ttest_izpis_xls");
define("M_REPORT_ANALIZA_XLS_BREAK_IZPIS", "break_izpis_xls");

define("A_REPORT_XLS_LIST", "list_xls");
define("A_REPORT_XLS_USABLE", "usable_xls");
define("A_REPORT_XLS_SPEEDER", "speeder_xls");
define("A_REPORT_XLS_TEXT_ANALYSIS", "text_analysis_xls");
define("A_REPORT_CSV_TEXT_ANALYSIS", "text_analysis_csv");
define("A_LANGUAGE_TECHNOLOGY_XLS", "lt_excel");


/*PPT*/
define("M_REPORT_ANALIZA_PPT_CHARTS", "charts_ppt");
define("M_REPORT_ANALIZA_PPT_HEATMAP_IMAGE", "heatmap_image_ppt");


/*IMAGE*/
define("M_REPORT_ANALIZA_HEATMAP_IMAGE", "heatmap_image");

/*XML*/
define("A_REPORT_VPRASALNIK_XML", "vprasalnik_xml");

?>