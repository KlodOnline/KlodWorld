# KlodWorld
## General Policies
  - This is not a democracy. This project is my vision of what an MMO-Strategy game should be, any people who share the vision is welcome, any suggestion is welcome, but the final word is mine.
  - Please READ & USE https://www.conventionalcommits.org/en/v1.0.0/
  - English is the main language of the project, to allow peoples from all over the world to participate
## To Do List
  - Add a README in _setup_ folder to explain how to setup a world 
  - Make code easier to read & work as a team
  - Check security of the whole authentication process
  - Easy World Setup : Check is setup.sh  launch the world in one script (name & passwords included)
## Purpose
"KlodWorld" is the game in itself. It's suppose to allow player to play, and game master to setup it. Each world can have it's own rules, defined with the game master interface.
## General organisation
to do.
## Setup
This is how you can setup a "KlodWorld" Server to test it on your own homelab :
  - Have a LAMP (either a VM, a CT, or baremetal)
  - Upload sources in /var/klodworld/
  - Go to /var/klodworld/setup
  - Edit "Manual variables" section of install.sh if usefull
  - run setup.sh with root privileges
You will need to add the world in the KlodWeb Database as indicated in the end of the script.
You serveur should be up and running on http://xxxx:443
