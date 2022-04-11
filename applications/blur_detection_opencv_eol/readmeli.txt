from: https://pyimagesearch.com/2015/09/07/blur-detection-with-opencv/
-----------------------------------------------------------------
I needed to do this on my Mac OS
$ pip install imutils
$ pip install opencv-python
--- end for Mac OS ---


Notes: eol-archive --- ran it but not sure if it helped:
yum install python-devel
yum install python27-python-pip
pip install scikit-build
  

WORKED OK IN eol-archive !!!
--- both installs worked OK and fixed problem in eol-archive
yum install numpy
  -> worked OK, installed numpy in Rhel
yum install opencv-python
  - worked OK, installed opencv in Rhel

-----------------------------------------------------------------
$ sudo pip install opencv-python
Password:
Collecting opencv-python
  Using cached opencv-python-4.5.5.62.tar.gz (89.9 MB)
  Installing build dependencies ... done
  Getting requirements to build wheel ... done
  Preparing metadata (pyproject.toml) ... done
Requirement already satisfied: numpy>=1.19.3 in /Users/eliagbayani/opt/anaconda3/lib/python3.9/site-packages (from opencv-python) (1.20.3)
Building wheels for collected packages: opencv-python
  Building wheel for opencv-python (pyproject.toml) ... done
  Created wheel for opencv-python: filename=opencv_python-4.5.5.62-cp39-cp39-macosx_10_14_x86_64.whl size=26255278 sha256=5248ca8026d96fa6a788053a07c97b952aeb0ce33cf806b08e704a34759ecfb7
  Stored in directory: /Users/eliagbayani/Library/Caches/pip/wheels/20/92/a3/45b0e84c435b8ee2eb14d3f47f925a8cc62754d66788d746ee
Successfully built opencv-python
Installing collected packages: opencv-python
Successfully installed opencv-python-4.5.5.62
WARNING: Running pip as the 'root' user can result in broken permissions and conflicting behaviour with the system package manager. It is recommended to use a virtual environment instead: https://pip.pypa.io/warnings/venv
WARNING: You are using pip version 21.3.1; however, version 22.0.3 is available.
You should consider upgrading via the '/Users/eliagbayani/opt/anaconda3/bin/python -m pip install --upgrade pip' command.
-----------------------------------------------------------------
Run program:
$ python detect_blur.py --images my_images
-> my_images is a folder where image filenames are stored


$ python detect_blur.py --images my_images --threshold 100
$ python detect_blur.py --images eol_images --threshold 100

convert -crop 25%x25% input.png output.png
convert -crop 25%x25% original.jpg output.jpg
convert -crop 25%x25% original.jpeg output.jpeg
