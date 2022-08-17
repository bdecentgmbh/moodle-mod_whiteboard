# Whiteboard activity

Moodle activity plugin to embed whiteboards into the Moodle course.

# Requirements

This plugin requires Moodle 4.0
In addition, you will need either a free or paid account for MIRO (https://miro.com/) or Conceptboard (https://conceptboard.com/). 
Your site must use SSL, otherwise the authenticaion of Conceptboard won't work.

# Motivation for this plugin

This plugin was built to enable teachers to improve collaboration with students using one of the popular whiteboard solutions. This was techincally already possible by using the URL activity, but having an activity for a specific purpose makes it easier for students to understand immediately what kind of content/activity their about to use. Also, the Whiteboard activity makes it easier for the teacher to add a whiteboard.

# Installation

Install the plugin like any other plugin to folder /mod/whiteboard 
See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins

# Initial Configuration

After installing the plugin, it is ready to use without the need for any configuration.
Admins can optionally choose to disabled one of the supported platforms (MIRO or Conceptboard).

# How to use

The Whiteboard activity is added like any other activity. Navigate to a course in which you have editing rights. Then turn on editing. Click on "Add an activity or resource" where you want to place the activity. Pick "Whiteboard".
Then, enter the following information: Name, Board ID and Type.
Please note: the board you're embedding must be configured to allow embedding. MIRO has a good tutorial on how to do this here https://help.miro.com/hc/en-us/articles/360016335640-How-to-embed-editable-boards-into-websites. Conceptboard does not, but it's fairly simple: Open your board and then click the "Share" button in the top right corner of your screen. Then adjust the permissions as needed.
In the Whiteboard activity, we only need the board ID, not the full link:
MIRO: https://miro.com/app/live-embed/uXjVPfdzxzc=/ —> the board ID is uXjVPfdzxzc=
Conceptboard: https://app.conceptboard.com/board/42za-8nk7-petm-xdao-aa3i —> the board ID is 42za-8nk7-petm-xdao-aa3i
In a future release, we plan to add authentication to the Whiteboard activity and then we'll be able to provide a "board picker" so that it won't be necessary anymore to enter an ID manually.
(Before we do so, we first want to gather feedback from the community to learn which whiteboard solutions are actually popular among Moodle users)

# Theme support

This plugin is developed and tested on Moodle Core's Boost theme. It should also work with Boost child themes, including Moodle Core's Classic theme. However, we can't support any other theme than Boost.

# Plugin repositories

This plugin will be published and regularly updated in the Moodle plugins repository: https://moodle.org/plugins/mod_whiteboard 
The latest development version can be found on Github: https://github.com/bdecentgmbh/moodle-mod_whiteboard

# Bug and problem reports / Support requests

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear. Please report bugs and problems on Github: https://github.com/bdecentgmbh/moodle-mod_whiteboard/issues We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.

# Feature proposals

Please issue feature proposals on Github: https://github.com/bdecentgmbh/moodle-mod_whiteboard/issues Please create pull requests on Github: https://github.com/bdecentgmbh/moodle-mod_whiteboard/pulls We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature proposals and not as feature requests.

# Moodle release support

This plugin is maintained for the two most recent major releases of Moodle as well as the most recent LTS release of Moodle. If you are running a legacy version of Moodle, but want or need to run the latest version of this plugin, you can get the latest version of the plugin, remove the line starting with $plugin->requires from version.php and use this latest plugin version then on your legacy Moodle. However, please note that you will run this setup completely at your own risk. We can't support this approach in any way and there is an undeniable risk for erratic behavior.

# Translating this plugin

This Moodle plugin is shipped with an english language pack only. All translations into other languages must be managed through AMOS (https://lang.moodle.org) by what they will become part of Moodle's official language pack.

# Copyright

bdecent gmbh bdecent.de
