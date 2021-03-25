===========================================================================================================================================
I was about to install pdf-to-text using this:
https://github.com/spatie/pdf-to-text

I was on the step:
On a mac you can install the binary using brew:
$ brew install poppler

-> after running $brew install poppler, the legacy eol_php_code/vendor/xpdf/pdftotext
suddenly came to life and is working

so I tried, in PHP codebase:
$ cd eol_php_code/vendor/xpdf/
$ pdftotext /Volumes/AKiTiO4/other_files/pdf2htmlEX/samples/SCtZ-0293-Hi_res.pdf
$ pdftotext /Volumes/AKiTiO4/other_files/pdf2htmlEX/samples/SCZ637-Foster_web-FINAL.pdf
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
another option: a ruby script: pdf2txt.rb
https://gist.github.com/emad-elsaid/9722831
gem install pdf-reader
-> install in Mac OS
ruby pdf2txt.rb /path-to-file/file1.pdf [/path-to-file/file2.pdf..]
ruby pdf2txt.rb SCZ637-Foster_web-FINAL.pdf

-> how to run

===========================================================================================================================================
another option: [pdftotext] https://www.cyberciti.biz/faq/converter-pdf-files-to-text-format-command/
- command-line utility in Linux distributions
In Linux eol-archive:
yum install poppler-utils
->install
$ pdftotext SCtZ-0293-Hi_res.pdf SCtZ-0293-Hi_res.pdf.txt
-> how to run

===========================================================================================================================================
START EPUB SERIES:

search in google: "convert epub to txt"
Zamzar online converter: OK!
https://www.zamzar.com/convert/epub-to-txt/
This also has an API: you need to sign up for an API key. Free 100 calls per month. 1MB max file size per call.
https://github.com/whyleyc/zamzar-php
converted:
https://editors.eol.org/other_files/temp/SCtZ-0293_epub.txt

https://github.com/whyleyc/zamzar-php

Hi Jen,
Definitely, if there is an .epub version then we should convert .epub to txt. Instead of the PDF file.
Here is our first file, converted from epub to txt:
https://editors.eol.org/other_files/temp/SCtZ-0293_epub.txt
Very clean result.

Anyway, I used an online converter for it (Zamzar).
https://www.zamzar.com/convert/epub-to-txt/

There is quite a number of online tools that does it.
And many also offer an API, but for a paid subscription.
Some API are free but for limited calls per day (e.g. Convertio)
https://convertio.co/epub-txt/
I'm currently working on this one.

Anyway, I tried converting our old PDF to txt using Zamzar and it is still problematic.
So .epub files is the way to go for those old PDF files.

Thanks.

---------------------- didn't send this portion yet
This also has an API, but we have to pay for it though.
The basic API account is $25.00, good for one month. With 500 credits. One .epub to txt conversion is 1 credit.
https://developers.zamzar.com/pricing

Anyway if we will use the paid API account.
We should get all the possible repositories
e.g. https://repository.si.edu/handle/10088/5097
So that we will only need a 1-month subscription and convert everything in one month.
Using the API we should be able to do this programmatically. That is convert all the .epub files to txt.

What do you think?
Thanks.
======================================================================= CONVERTIO, another epub - txt converter - with working API
https://developers.convertio.co/
email = eagbayani@eol.org
pw = ile173xyz
Your personal API Key --- 0faab539f8de23cd027d32cbddd6b620
You have a daily limit of 25 conversion minutes. To extend this limit, you can buy prepaid packages
You have 10 OCR pages left.
You may buy prepaid packages to increase OCR pages limit
----------------- email confirmation
Hello,
Thank you for registering for Convertio!
We noticed your registration is incomplete and you’re almost finished!
Please finalize your sign-up here.
https://convertio.co/pricing/?utm_source=mail
If you experienced a technical issue, please see our knowledge base at https://support.convertio.co/ or contact us at support@convertio.co, we’re happy to help.
Your feedback is important to us and helps us improve our services.
The Convertio Team
-----------------

https://editors.eol.org/other_files/temp/SCtZ-0293.epub

curl -i -X POST -d '{"apikey": "_YOUR_API_KEY_", "file":"http://google.com/", "outputformat":"png"}' http://api.convertio.co/convert

--------------------------- SAMPLE IF INPUT IS URL ---------------------------
STEP 1:
curl -i -X POST -d '{"apikey": "eli_api_key", "file":"https://editors.eol.org/other_files/temp/SCtZ-0293.epub", "outputformat":"txt"}' http://api.convertio.co/convert
-> start conversion
{"code":200,"status":"ok","data":{"id":"63ce87a42ab51fa45c0ef5af8b329612","minutes":25}}

STEP 2:
curl -i -X GET http://api.convertio.co/convert/63ce87a42ab51fa45c0ef5af8b329612/status
-> get status
{"code":200,"status":"ok","data":{"id":"63ce87a42ab51fa45c0ef5af8b329612","step":"finish","step_percent":100,"minutes":"1",
  "output":{"url":"https:\/\/s163.convertio.me\/p\/PiX4oKMhB9YQZYnX_k-OIg\/0faab539f8de23cd027d32cbddd6b620\/SCtZ-0293.txt","size":"272899"}}}
- end -
--------------------------- SAMPLE IF INPUT IS TO POST A LOCAL FILE ---------------------------
STEP 1:
curl -i -X POST -d '{"apikey": "0faab539f8de23cd027d32cbddd6b620", "input":"upload", "outputformat":"txt"}' http://api.convertio.co/convert
-> start
id = a1528fe98081ef0f9fa5f4eebd54ddd2
id = b6247685a3926973b95b4b6edf55800d

This step required only if chooses input = 'upload' on previous step. 
In order to upload file for conversion, you need to do a following PUT request
STEP 2:
curl -i -X PUT --upload-file 'SCtZ-0293.epub' http://api.convertio.co/convert/a1528fe98081ef0f9fa5f4eebd54ddd2/SCtZ-0293.epub
curl -i -X PUT --upload-file 'SCtZ-0293.epub' http://api.convertio.co/convert/b6247685a3926973b95b4b6edf55800d/SCtZ-0293.epub
-> PUT request for local file

STEP 3:
curl -i -X GET http://api.convertio.co/convert/a1528fe98081ef0f9fa5f4eebd54ddd2/status
curl -i -X GET http://api.convertio.co/convert/b6247685a3926973b95b4b6edf55800d/status

-> get status
{"code":200,"status":"ok","data":{"id":"a1528fe98081ef0f9fa5f4eebd54ddd2","step":"finish","step_percent":100,"minutes":"1",
  "output":{"url":"https:\/\/s168.convertio.me\/p\/PiX4oKMhB9YQZYnX_k-OIg\/0faab539f8de23cd027d32cbddd6b620\/SCtZ-0293.txt","size":"272899"}}}
{"code":200,"status":"ok","data":{"id":"b6247685a3926973b95b4b6edf55800d","step":"finish","step_percent":100,"minutes":"1",
  "output":{"url":"https:\/\/s169.convertio.me\/p\/PiX4oKMhB9YQZYnX_k-OIg\/0faab539f8de23cd027d32cbddd6b620\/SCtZ-0293.txt","size":"272899"}}}

- end -

CONCLUSION: Zamzar's online tool has the same output as Convertio's API command-line output.