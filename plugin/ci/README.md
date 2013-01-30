
cmd:
    yum install python26 -b current [5U]
    yum install python-setuptools
    /usr/bin/easy_install pip
    pip install paramiko
    pip install scpclient
    pip install simplejson [5u]

dependency:
    python26
    paramiko
    pycrypto
    scpclient

optional:
    rpm_create
