#!/bin/bash
set -e

service mariadb start

exec "$@"
