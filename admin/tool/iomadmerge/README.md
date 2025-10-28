# IOMAD

<p align="center"><a href="https://www.iomad.org" target="_blank" title="IOMAD Website">
  <img src="https://avatars.githubusercontent.com/u/5493428?v=4" alt="The IOMAD Logo">
</a></p>

The IOMAD admin tool IOMAD merge is a clone of the tool_merge plugin developed and maintained by Nocolas Dunand. This version is tenant aware.

This plugin is part of the IOMAD suite of plugins. It must be installed with all other plugins from the suite and the core code patch must also be applied in order for these to work.

## Installing via uploaded ZIP file ##

Log in to your Moodle site as an admin and go to _Site administration > Plugins > Install plugins_.
Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.
Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by adding the contents of this directory to

    {your/moodle/dirroot}/mod/iomadcertificate

Afterwards, log in to your Moodle site as an admin and go to _Site administration > Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##
2010+ e-Learn Design Ltd. https://www.e-learndesign.co.uk
IOMAD is a registered trademark in the UK belonging to Derick Turner

GNU GPL v3 or later. http://www.gnu.org/copyleft/gpl.html

Contributors
============

Maintained by:

* Nicolas Dunand.
* [Jordi Pujol-Ahulló](https://recursoseducatius.urv.cat).

[See all Github contributors](https://github.com/ndunand/moodle-tool_iomadmerge/graphs/contributors)
