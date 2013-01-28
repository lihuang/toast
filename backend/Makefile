MODULES = log net poll threadpool/linux config sync daemon pty thread agent



ifeq ($(coverage), yes)
MAKE	= make coverage=yes
else
MAKE	= make
endif

build:
	echo "build ..."
	for mod in ${MODULES}; do \
		echo "=> $$mod";  \
		(cd $$mod; ${MAKE}); \
		echo "<= $$mod";  \
	done

clean:
	echo "clean ..."
	for mod in ${MODULES}; do \
		echo "=> $$mod";        \
		(cd $$mod; make clean); \
		echo "<= $$mod";        \
	done


