Changelog stomp-php
-------------------

4.0.0 (Full Stomp 1.1 / 1.2 Support)
------------------------------------

- Updated functional testsuite for different brokers (amq,aplo,rabbit), update travis-ci.
- Update Parser in order to be compliant with stomp-1.2 (https://stomp.github.io/stomp-specification-1.2.html)
- ACK Mode is now `auto` by default. 
- Implement StateMachine Pattern `StatefulStomp`.
- Restructure project, new Namespaces `Protocol`, `Transport`, `Network`.
- Make Client much smaller, remove all non low level methods to `SimpleStomp`.
- Testsuite rework...
- Move examples to https://github.com/stomp-php/stomp-php-examples.
- add utils for `ActiveMq` durable subscription and `Apollo` queue browser.

4.0.1
-----
- do not throw read exception if the next byte after a complete read is a zero byte. (https://github.com/stomp-php/stomp-php/issues/39)
- allow to reset the parser internal state and buffer. (https://github.com/stomp-php/stomp-php/issues/40)

4.1.0
-----
- fixed header escaping in legacy mode in Parser (28ed8b6)
- when the Parser is working in legacyMode it should produce Frames working in legacyMode (d257b98)
- validate given ack SUBSCRIBE frame values against the STOMP spec (8043970)
- Added a OpenMq Broker (0638958)
- updated AMQ ci test version from 5.13.0 to 5.13.3 (ee37df6)
- updated RMQ ci test version from 3.5.6 to 3.6.2 (ee37df6)
- updated durable tests for RMQ (pass 'auto-delete' header and replaced 'persistent' with 'durable' header) (ee37df6)
- updated erlang from R14B04 to latest version (travis) (ee37df6)
- increase read timeout for amq abort test (ee37df6)

4.1.1
-----
- fixed Null-Byte handling, thanks to @andrewbelcher (https://github.com/stomp-php/stomp-php/issues/54)

 
4.1.2
-----
- update nack and ack Frames according to stomp 1.1 specification, thanks to @rawkode (https://github.com/stomp-php/stomp-php/issues/56)

4.1.3
-----
- persistent connection flag for socket, thanks to @tenerd (https://github.com/stomp-php/stomp-php/issues/57)

4.1.4
-----
- add headers getter in Frame class, thanks to @surrealcristian (https://github.com/stomp-php/stomp-php/issues/61)

4.1.5
-----
- fix for false exceptions in combination with pcntl_signal (https://github.com/stomp-php/stomp-php/pull/65)

4.2.0
-----
- fix invalid class hierarchy for `Stomp\Transport\Map` changed parent from `Frame` to `Message`
- add option to register own message type handlers in `Stomp\Transport\FrameFactory` (https://github.com/stomp-php/stomp-php/pull/64)

4.2.1
-----
- Update Connector to support `_`  in the broker uri, thanks to @campru (https://github.com/stomp-php/stomp-php/pull/66)