===========================================================================================================================================
I was about to install pdf-to-text using this:
https://github.com/spatie/pdf-to-text

I was on the step:
On a mac you can install the binary using brew:
$ brew install poppler

-> after running $brew install poppler, the legacy eol_php_code/vendor/xpdf/pdftotext
suddenly came to life and is working

so I tried, in PHP codebase:
$ cd eol_php_code/vendor/xpdf/pdftotext
$ pdftotext /Volumes/AKiTiO4/other_files/pdf2htmlEX/samples/SCtZ-0293-Hi_res.pdf
And it worked, it generated a cleaner text version of the PDF file.

*I no longer did use and continue installation of https://github.com/spatie/pdf-to-text
It is still in:
/Library/WebServer/Documents/pdf-to-text-1.4.0:
===========================================================================================================================================
During initial exploring:

- Installed pdf2htmlEX in eol-archive.
Successfully generated HTML version of our test PDF (SCtZ-0293-Hi_res.pdf).
https://repository.si.edu/bitstream/handle/10088/6296/SCtZ-0293-Hi_res.pdf
HTML version: https://editors.eol.org/other_files/pdf2htmlEX/SCtZ-0293-Hi_res.html.zip

Useful: https://github.com/pdf2htmlEX/pdf2htmlEX/wiki/Quick-Start
pdf2htmlEX --zoom 1.3 SCtZ-0293-Hi_res.pdf
pdf2htmlEX --embed cfijo --dest-dir out SCtZ-0293-Hi_res.pdf

https://software.opensuse.org/download.html?project=home%3Ajustlest%3Apdf2htmlEX&package=pdf2htmlEX#manualCentOS
https://software.opensuse.org/download.html?project=home%3Ajustlest%3Apdf2htmlEX&package=pdf2htmlEX#manualCentOS
https://software.opensuse.org/download.html?project=home%3Ajustlest%3Apdf2htmlEX&package=pdf2htmlEX --- best works!!! install pdf2htmlEX in eol-archive

===========================================================================================================================================
Another during initial exploring:
- installed locally [PDF Parser]. Generated text version of our test PDF
https://editors.eol.org/other_files/temp/pdf2text_output.txt
https://www.pdfparser.org/documentation


===========================================================================================================================================
===========================================================================================================================================
===========================================================================================================================================
