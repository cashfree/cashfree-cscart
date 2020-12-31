## Cashfree Payment Extension for CSCART

Allows you to use Cashfree payment gateway with the CSCART.

## Description

This repository contains integration code for interaction with the Cashfree API and allows payment in CSCART seamlessly.

## Installation

1. Ensure you have latest version of CSCART installed.
2. Download the zip of this repo.
3. You need to execute 'install_cashfree.sql' against CSCART database, file is available in root directory of this repo. Change prefix if you have added during installation of CSCART. You can either use phpmyadmin to import sql file directly or run sql queries in your mysql shell.
4. Upload the contents of the repo to your CSCART Installation directory (content of app folder goes in app folder and content of design goes in design folder).

## Configuration

1. Log into CSCART as administrator (http://cscart_installation/admin.php).
2. Navigate to Administration / Payment Methods.
3. Click the "+" to add a new payment method.
4. Fill Name as cashfree.
5. Choose Cashfree from the list and template, choose "cc_outside.tpl".
6. Fill the App Id and Secret Key.
7. Chosse currency from the dropdown.
8. Choose if you want to enable test mode or not.
9. Click 'Create'

### Support

For further queries, reach us at techsupport@gocashfree.com .