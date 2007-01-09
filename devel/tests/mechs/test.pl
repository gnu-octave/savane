#!/usr/bin/perl

use strict;
use warnings;
use WWW::Mechanize;
use Test::More qw(no_plan);

use Savane;


my $agent = WWW::Mechanize->new();

# Login in
$agent->get('http://localhost/savane/');
$agent->follow_link(text => 'Login', n => '1');

$agent->form_number(2);
$agent->field('form_loginname', 'Beuc');
$agent->field('form_pw', 'beuc');
$agent->click('login');


# Create a new project
$agent->follow_link(text => 'Register New Project', n => '1');
$agent->form_number(2);
$agent->click('Submit');

$agent->form_number(2);
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_comments', '');
# Test string escaping / quoting
$agent->field('form_purpose', '\'');
$agent->field('form_required_sw', '');
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_unix_name', 'test');
$agent->field('form_full_name', 'test');
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_license', 'gpl');
$agent->field('form_license_other', '');
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_comments', '');
$agent->field('form_purpose', '\'');
$agent->field('group_type', '1');
$agent->field('form_required_sw', '');
$agent->field('form_license', 'gpl');
$agent->field('form_full_name', 'test');
$agent->field('form_license_other', '');
$agent->click('i_agree');


# Log out
$agent->follow_link(text => 'Logout', n => '1');


# Check that the project exists
ok(GetGroupSettings('test', 'status') eq 'P', 'Check project status');

# Check that the associated task was created
# TODO
