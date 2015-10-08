Installation
1. Clone the entire repository into the directory <moodle>/local and then run shifter to
   build the javascript. The commands to achieve this on a linux machine are (provided
   you have shifter installed):

   cd <moodlehome>/moodle
   git clone https://github.com/DevonKS/autotagger.git
   cd autotagger
   shifter

   or if you want to use grunt just replace the 'shifter' command with 'grunt'

   Note: if shifter is not installed then it can be installed on linux machines with a
   command similar to:
   sudo apt-get install nodejs npm
   sudo npm install shifter@0.4.6 -g

   Note: if grunt is not installed then it can be installed on linux machines with a
   command similar to:
   sudo apt-get install nodejs npm
   sudo npm install
   sudo npm install -g grunt-cli

2. The plugin requires the Yaml PHP extension. The command to install this on a linux machine are:

   sudo apt-get install libyaml-dev
   sudo pecl install yaml
