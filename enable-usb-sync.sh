#!/bin/bash

# Stop on the first sign of trouble
set -e

SILENT_INSTALL=false

function info {
    echo -e "\033[0;36m${1}\033[0m"
}

function error {
    echo -e "\033[0;31m${1}\033[0m"
}

#Param 1: Question / Param 2: Default / silent answer
function ask_yes_no {
    if [ "$SILENT_INSTALL" = false ]; then
        read -p "${1}: " -n 1 -r
    else
        REPLY=${2}
    fi
}

if [ $UID != 0 ]; then
    error "ERROR: Only root is allowed to execute the installer. Forgot sudo?"
    exit 1
fi

if [ ! -f /proc/device-tree/model ]; then
    error "ERROR: This installer is only intended to run on a Raspberry Pi."
    exit 2
fi

PI_MODEL=$(tr -d '\0' </proc/device-tree/model)

if [[ $PI_MODEL != Raspberry* ]]; then
    error "ERROR: This installer is only intended to run on a Raspberry Pi."
    exit 3
fi

view_help() {
    SCRIP=$(basename $0)
    cat << EOF
Usage: sudo bash $SCRIPT -u=<YourUsername> [-hsV]

    -h,  -help,       --help        Display help.

    -s,  -silent,     --silent      Run silent installation.

    -u,  -username,   --username    Enter your OS username you're using Photobooth from.

    -V,  -verbose,    --verbose     Run script in verbose mode.
EOF
}

check_username() {
    info "[Info]      Checking if user $USERNAME exists..."
    if id "$USERNAME" &>/dev/null; then
        info "[Info]      User $USERNAME found. Installation process continues."
    else
        error "ERROR: An valid OS username is needed! Please re-run the installer."
        view_help
        exit
    fi
}

options=$(getopt -l "help,username::,silent,verbose" -o "hu::sV" -a -- "$@")
eval set -- "$options"

while true
do
    case $1 in
        -h|--help)
            view_help
            exit 0
            ;;
        -u|--username)
            shift
            USERNAME=$1
            info "### Username: $1"
            ;;
        -s|--silent)
            SILENT_INSTALL=true
            info "### Silent installation starting..."
            ;;
        -V|--verbose)
            set -xv
            info "### Set xtrace and verbose mode."
            ;;
        --)
        shift
        break;;
    esac
    shift
done


if [ ! -z $USERNAME ]; then
    check_username
else
    error "ERROR: An valid OS username is needed! Please re-run the installer."
    view_help
    exit
fi

info "### Disabling automount for user $USERNAME"
mkdir -p /home/$USERNAME/.config/pcmanfm/LXDE-pi/

cat >> /home/$USERNAME/.config/pcmanfm/LXDE-pi/pcmanfm.conf <<EOF
[volume]
mount_on_startup=0
mount_removable=0
autorun=0
EOF
chown -R $USERNAME:$USERNAME /home/$USERNAME/.config

info "### Adding polkit rule so www-data can (un)mount drives"
cat >> /etc/polkit-1/localauthority/50-local.d/udisks2.pkla <<EOF
[Allow www-data to mount drives with udisks2]
Identity=unix-user:www-data
Action=org.freedesktop.udisks2.filesystem-mount*;org.freedesktop.udisks2.filesystem-unmount*
ResultAny=yes
ResultInactive=yes
ResultActive=yes
EOF

echo -e "\033[0;33m"
ask_yes_no "### You need to reboot your device. Do you like to reboot now? [y/N] " "N"
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    info "### Your device will reboot now."
    shutdown -r now
else
    info "### Done. Please reboot your device."
fi
