#### DOCKER FILE
##  Test Image building for Peerstreamer service
##  :: docker build -t <name> - < ps_image.dockerfile
##  will build the image without working dir, from the dockerfile 
####

FROM philcryer/min-wheezy:latest
MAINTAINER cloudy@ac.upc.edu

ENV pspath=/var/local/cDistro/plug/resources/peerstreamer \
	psweb="https://raw.githubusercontent.com/Clommunity/build-peerstreamer/master/bin/"

RUN apt-get update
RUN apt-get install -y curl
RUN apt-get install -y --force-yes vlc-nox
RUN apt-get install -y --force-yes libav-tools
RUN apt-get install -y lsof 
RUN apt-get install -y --force-yes git gcc g++ subversion libtool autoconf yasm texinfo libx11-dev libxext-dev curl

RUN echo "deb http://http.debian.net/debian wheezy-backports main" >> /etc/apt/sources.list
RUN apt-get update
RUN apt-get -t wheezy-backports install -y --force-yes jq 
RUN apt-get install -y --force-yes libsdl-sound1.2 libsdl-sound1.2-dev

## Not sure why but instead of 52 is now 53
RUN apt-get install -y --force-yes libavcodec53 libavdevice53 libavformat53
RUN apt-get install -y --force-yes libxext-dev

### Some packages may not be necessary, but it seems peerstreamer runs into trouble without them
#If we need more packages we can to install here

#$(uname -m ) does not work; perhaps we need to: RUN un=$(uname -m) instead
ENV un=amd64
RUN mkdir -p /opt/peerstreamer ;\
	curl -k https://raw.githubusercontent.com/Clommunity/build-peerstreamer/master/bin/amd64/streamer-udp-grapes-static -o /opt/peerstreamer/streamer-udp-grapes-static ;\
	chmod 0755 /opt/peerstreamer/streamer-udp-grapes-static

#Maybe we need to cp from outside instead of ADD
# and maybe not using the ENV variable
RUN mkdir -p /var/local/cDistro/plug/resources/peerstreamer/

## Add works if it comes from web, otherwise it will look from base dir (which there is none when using STDIN as the dockerfile)
ADD http://10.1.26.2:7000/plug/resources/peerstreamer/pscontroller /var/local/cDistro/plug/resources/peerstreamer/pscontroller
ADD http://10.1.26.2:7000/plug/resources/peerstreamer/ps_shell /var/local/cDistro/plug/resources/peerstreamer/ps_shell

## Changing pscontroller script because there is no ip_local_port_range available
RUN sed -i -e 's-\tread lowerPort upperPort < \/proc\/sys\/net\/ipv4\/ip_local_port_range$-\tlowerPort=32768\n\tupperPort=61000-' /var/local/cDistro/plug/resources/peerstreamer/pscontroller

RUN mkdir -p /var/local/cDistro/plug/resources/monitor-aas/
ADD http://10.1.26.2:7000/plug/resources/monitor-aas/common.sh /var/local/cDistro/plug/resources/monitor-aas/common.sh

# These are just for testing
ENV urlstream=rtsp://10.139.40.81:554/live/ch01_0 \
	port=6410 \
	device=eth0 \
	description="Testing"

# Perhaps the start of service CMD should be given after starting the container and not when building
#CMD /bin/bash /var/local/cDistro/plug/resources/peerstreamer/pscontroller publish $urlstream $port $device $description

# Two ways to have open ports to outside
# EXPOSE from the dockerfile (means we need to know previously the port)
# or when launching container: docker run -p port:port .....
## by default should be when running
# EXPOSE 6410 6410

#STOPSIGNAL maybe should be use to unpublish the PS or terminate the service

##### Two ways of starting service (internal or external):
## Internal: when building image with the dockerfile, either CMD or RUN can be given to activate the service
####
## External: when we want to run a container we can do it as,
##    i.e. docker run -p p:p -t -i -d ps_image /bin/bash -c '..../pscontroller publish <arguments here! that can come from outside cont$
##    the container needs an active foreground process to continue otherwise it will stop it. command line can be good to send out comm$
####
## To stop the service/container: docker exec <container> /bin/bash ..../pscontroller unpublish port
##                                docker stop <container>
#############

### Add after to use the cached builds of the images

RUN sed -i -e 's/.$SHELL "start"/#$SHELL "start"/g' /var/local/cDistro/plug/resources/peerstreamer/pscontroller
