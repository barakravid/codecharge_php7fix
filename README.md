# codecharge_php7fix
script to transform code charge studio output to php 7.2
----------------------------------------------------------------------
I've developed a script to transform CCS php files to work with PHP7.2. includes changing function and constructor names,
each iterations etc. I don't take any responsibility, but I am using this regularly.

Usage from command line:

php conv_fnames.php <path to project dir after publish>

Notice:

Run this after each publish. 
It is set to ignore directory CCS if its located inside your publish directory so not to touch the original. 
And set to ignore node_modules - if you are using node for any reason.

It assumes you haven't touched the CodeCharge library files such as classes.php etc - 
which can always be regenerated from scratch.
