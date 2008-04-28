QSOS for limesurvey small user guide
===================================

1 - What is QSOS ?
------------------

QSOS stands for "Qualification and Selection of Open Source software", this is an opensource project that aims at helping users to choose the best opensource tools that meet their needs and constraints by evaluating all opensource softwares that relates to the same subject given some predefined criteria: what is left to the end user is then to assign priority for each evaluated topic in order to select the best opensource software for him.

The QSOS project is hosted at:
https://savannah.nongnu.org/cvs/?group=qsos

2 - The QSOS template file for survey-tool
------------------------------------------

QSOS project has already defined a template for survey-tool evaluation (work has been done by Olivier Portier in France).

Thibault Le Meur (lemeur) has started a translation of this template into english so that it can be used to compare our competitors and adjust our roadmap for LimeSurvey 2.0. 

In order to compile the survey-tool-en.qsos file you need:
* the include/ directory with the generic_fr.qin and generic.qin files
* the translated and updated survey-tool_en.qtpl file

Compilation is done by invoking:
# ./createemptysheet.pl -qtpl survey-tool_en.qtpl > survey-tool-en.qsos

This qsos file can then be used in the 'Xuleditor', a module component of
Firefox, in order to create a new evaluation file. Xuleditor is an opensource
software produced by the QSOS team and available at:
http://www.qsos.org/?lp_lang_pref=en&page_id=5


