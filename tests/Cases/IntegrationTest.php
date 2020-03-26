<?php

declare(strict_types=1);
/**
 * This file is part of Reasno/GoTask.
 *
 * @link     https://www.github.com/reasno/gotask
 * @document  https://www.github.com/reasno/gotask
 * @contact  guxi99@gmail.com
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\Cases;

use Reasno\GoTask\GoTask;
use Reasno\GoTask\Relay\CoroutineSocketRelay;
use Reasno\GoTask\Relay\RelayInterface;
use Spiral\Goridge\Exceptions\ServiceException;
use Spiral\Goridge\RPC;
use Swoole\Process;

/**
 * @internal
 * @coversNothing
 */
class IntegrationTest extends AbstractTestCase
{
    /**
     * @var RPC
     */
    private $task;

    public function setUp()
    {
        $unixSocket = '/tmp/test.sock';
        $p = new Process(function (Process $process) use ($unixSocket) {
            $process->exec(__DIR__ . '/../../app', ['-address', $unixSocket]);
        });
        $p->start();
        $this->task = new RPC(
            new CoroutineSocketRelay($unixSocket,  null, CoroutineSocketRelay::SOCK_UNIX)
        );
    }

    public function testExample()
    {
        \Swoole\Coroutine\run(function(){
            sleep(1);
            $this->assertEquals(
                'Hello, Reasno!',
                $this->task->call('App.HelloString', 'Reasno')
            );
            $this->assertEquals(
                ['hello' => ['jack', 'jill']],
                $this->task->call('App.HelloInterface', ['jack', 'jill'])
            );
            $this->assertEquals(
                ['hello' => [
                    'firstName' => 'LeBron',
                    'lastName' => 'James',
                    'id' => 23,
                ]],
                $this->task->call('App.HelloStruct', [
                    'firstName' => 'LeBron',
                    'lastName' => 'James',
                    'id' => 23,
                ])
            );

            $this->assertEquals(
                'My Bytes',
                $this->task->call('App.HelloBytes', base64_encode('My Bytes'), RelayInterface::PAYLOAD_RAW)
            );
            try{
                $this->task->call('App.HelloError', 'Reasno');
            } catch (\Throwable $e){
                $this->assertInstanceOf(ServiceException::class, $e);
            }

        });
    }
}
