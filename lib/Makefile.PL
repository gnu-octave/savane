# Attempt to pro-PERLy install the Savane module.

# 'MANIFEST' contains the canonical list of files to install. Use
# 'make manifest' to update it.

use ExtUtils::MakeMaker;
# See lib/ExtUtils/MakeMaker.pm for details of how to influence
# the contents of the Makefile that is written.
WriteMakefile(
    'NAME'		=> 'Savane',
    'FIRST_MAKEFILE'    => 'Makefile.perl',
    'VERSION_FROM'      => 'Savane/Version.pm',
);

#    'PREREQ_PM'		=> {Mail::Send Term::ANSIColor DBI File::Basename Text::Wrap libdbd-mysql-perl libxml-writer-perl libfile-find-rule-perl libterm-readkey-perl libdate-calc-perl libstring-random-perl}, # e.g., Module::Name => 1.1

# ../REQUIREMENTS: DBI DBD::mysql Digest::MD5 Mail::Send
#   File::Find::Rule XML::Writer IO::File Date::Calc.pm String::Random
#   (for sv_mailman)
