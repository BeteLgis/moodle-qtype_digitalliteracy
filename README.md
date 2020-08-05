# DIGITAL LITERACY
Version 0.9.0 August 2020

Authors: Ainur Sharipov and Fedor Deryabin, Higher School of Economics, Russia
# INSTALLATION
DigitalLiteracy requires two separate plug-ins: one for the question type and one for the special interactive behaviour. 

The plug-ins are in two different github repositories: https://github.com/BeteLgis/moodle-qtype_digitalliteracy and https://github.com/BeteLgis/moodle-qbehaviour_interactive_for_digitalliteracy. 

Install the two plugins using one of the following two methods.

1.1 Download the zip file of the required branch from the digitalliteracy github repository.

1.2 Unzip the zip file into the directory moodle/question/type and change the name of the newly-created directory from moodle-qtype_digitalliteracy-<branchname> to just digitalliteracy. 

1.3 Similarly download the zip file of the required question behaviour from the behaviour github repository, unzip it into the directory moodle/question/behaviour and change the newly-created directory name to interactive_for_digitalliteracy.
  
OR

Get the code using git by running the following commands in the top level folder of your Moodle install:

2.1 git clone https://github.com/BeteLgis/moodle-qtype_digitalliteracy question/type/digitalliteracy

2.2 git clone https://github.com/BeteLgis/moodle-qbehaviour_interactive_for_digitalliteracy question/behaviour/interactive_for_digitalliteracy

3. You can then complete the installation by logging onto the server through the web interface as an administrator and following the prompts to upgrade the database as appropriate.
