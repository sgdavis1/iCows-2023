# iCows Drupal 9 Development Environment

This repo should contain a completely self-contained Drupal 9 system inside of two containers based
on one custom image, and a generic MariaDB image. Using `docker-compose` and the supplied `bash`
scripts (using the Windows Subsystem for Linux on Windows environments) should enable cross
platform, machine independent development. See **NOTES** section for help with troubleshooting

## Quick Start

### Configure your settings

Using the example configuration file `docker/icows.conf.example`, setup the local port and usernames
and passwords that you will use locally in your installation.

These settings are used internally by all of the scripts and do not need to be changed once created.
Do not commit these settings into the source code repository.

### Start the system

Execute the `start.sh` script. Pay attention to the startup logging to watch for warnings and
errors. Often any issues can be identified during this startup phase from reading the logs:

  ```
  cd docker/scripts
  ./start.sh
  ```

### Access the system

Access the system with your web browser:

* The URL for the environment will match the `LOCAL_PORT` settings from your `.conf`
  ```
  http://localhost:8080
  ```
* Login with the admin user credentials from the seed database:
  > **username:** `icows-admin`

  > **password:** `^o4H+.(nh+(Sv'*%=T`
  


## NOTES

For Windows users, we often see a line-ending issue that causes problems when using WSL to execute
the supplied scripts. Try adding the following git settings (either globally, or locally and sync
the repository) and line ending issues may be avoided:

  ```
  autocrlf = input
  eol = crlf
  ```

You can set this globally with the following command:

  ```
  git config --global autocrlf "input"; git config --global eol "crlf"
  ```

