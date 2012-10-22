<?php

namespace FuseSource\Stomp;

function stream_select()
{
    global $fusesourceStreamFunctionStubsBuffer;
    if (count($fusesourceStreamFunctionStubsBuffer) === 0) {
        return false;
    }
    return strlen($fusesourceStreamFunctionStubsBuffer[0]);
}

function fread($socket, $count)
{
    global $fusesourceStreamFunctionStubsBuffer;
    return array_shift($fusesourceStreamFunctionStubsBuffer);
}

function feof()
{
    global $fusesourceStreamFunctionStubsBuffer;
    return count($fusesourceStreamFunctionStubsBuffer)===0;
}