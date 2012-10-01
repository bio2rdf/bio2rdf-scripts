#ChEMBL

This is the parser for the ChEML database version 14 parser

things to do:

	1. section on installing mysql (creating users, tables, etc)

## Mysql Installation and Configuration

Below are the instructions to get a mysql database up and running on the system that you plan to use.

###Mac

The easiest way is to install the HomeBrew package manager. Instructions can be found 'here'. Once installed type the following in the 
command line:

> brew install mysql

##Database Installation

1. Log into MySQL database server where you intend to load chembl data and
   run the following command to create new database:

    mysql> create database chembl_14;

2. Logout of database and run the following command to laod data. You will
   need to replace USERNAME, PASSWORD, HOST and PORT with local settings. 
   Depending on your database setup you may not need the host and port
   arguments. 
   
    $> mysql -uUSERNAME -pPASSWORD [-hHOST -PPORT] chembl_14 < /path/to/chembl_14.mysqldump.sql

