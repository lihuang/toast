Name: t-test-toast
Version: 1.0.0
Release: %(echo $RELEASE)%{?dist}

Summary: toast agent
URL: http://svn.simba.taobao.com/svn/QA/automation/trunk/toast/newengine/
Group: taobao/test
License: Commercial

%description
toast agent
%{_svn_path}
%{_svn_revision}

%define _prefix /home/a

%build
cd ..; cd ..; cd ..; pwd; make clean; make

%install
mkdir -p .%{_prefix}/bin/toastd/
cp ../../../agent/AgentDaemon.conf  .%{_prefix}/bin/toastd/
cp ../../../agent/toast             .%{_prefix}/bin/toastd/
cp ../../../agent/toastd.conf       .%{_prefix}/bin/toastd/
cp ../../../agent/toastdaemon.py         .%{_prefix}/bin/toastd/
cp ../../../agent/toastd.log        .%{_prefix}/bin/toastd/
cp ../../../agent/toastupdate.py    .%{_prefix}/bin/toastd/
cp ../../../agent/toastdaemon       .%{_prefix}/bin/toastd/

%files
%defattr(755,ads,ads)

%{_prefix}

%post
cp /home/a/bin/toastd/toastdaemon /etc/init.d/
chkconfig --add toastdaemon
/sbin/service toastdaemon start