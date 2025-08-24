<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite;

use FastRoute\BadRouteException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lav45\MockServer\DispatcherFactoryInterface;
use Lav45\MockServer\Watcher;
use Lav45\Watcher\Event;
use Lav45\Watcher\WatcherInterface as ExternalFileWatcherInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function FastRoute\simpleDispatcher;

final class WatcherTest extends TestCase
{
    private FakeDispatcherFactory $fakeDispatcherFactory;

    private LoggerInterface $logger;

    private string $watchDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDispatcherFactory = new FakeDispatcherFactory();
        $this->logger = new NullLogger();

        $this->watchDir = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mock_server_watcher_test_' . \uniqid('', true);
        if (!\mkdir($this->watchDir, 0777, true) && !\is_dir($this->watchDir)) {
            $this->fail("Failed to create temporary directory: {$this->watchDir}");
        }
        if (!\mkdir($this->watchDir . DIRECTORY_SEPARATOR . 'subdir', 0777, true) && !\is_dir($this->watchDir . DIRECTORY_SEPARATOR . 'subdir')) {
            $this->fail("Failed to create temporary subdirectory");
        }
        if (!\mkdir($this->watchDir . DIRECTORY_SEPARATOR . '__ignored_dir', 0777, true) && !\is_dir($this->watchDir . DIRECTORY_SEPARATOR . '__ignored_dir')) {
            $this->fail("Failed to create temporary ignored directory");
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (\is_dir($this->watchDir)) {
            $this->removeDirectory($this->watchDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }
        $items = \array_diff(\scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            \is_dir($path) ? $this->removeDirectory($path) : \unlink($path);
        }
        \rmdir($dir);
    }

    private function createMockFile(string $filename, string $content, string $subdir = ''): string
    {
        $dir = $this->watchDir;
        if ($subdir) {
            $dir .= DIRECTORY_SEPARATOR . $subdir;
            if (!\is_dir($dir)) {
                \mkdir($dir, 0777, true);
            }
        }
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        \file_put_contents($path, $content);
        return $path;
    }

    public function testInitLoadsValidMockFilesAndInitializesDispatcher(): void
    {
        $mockData1 = [
            ['request' => ['url' => '/test1', 'method' => 'GET'], 'response' => ['text' => 'Test 1']],
        ];
        $this->createMockFile('mock1.json', \json_encode($mockData1));

        $mockData2 = [
            ['request' => ['url' => '/test2', 'method' => 'POST'], 'response' => ['text' => 'Test 2']],
        ];
        $this->createMockFile('mock2.json', \json_encode($mockData2), 'subdir');

        $this->createMockFile('ignored.txt', 'some text');
        $this->createMockFile('.ignored.json', \json_encode([]));
        $this->createMockFile('in_ignored_dir.json', \json_encode([]), '__ignored_dir');

        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $this->logger);
        $watcher->init();

        $this->assertSame(1, $this->fakeDispatcherFactory->getCreateCallCount(), "DispatcherFactory::create should be called once during init");

        $expectedMocksDataForDispatcher = [
            $this->watchDir . DIRECTORY_SEPARATOR . 'mock1.json' => $mockData1,
            $this->watchDir . DIRECTORY_SEPARATOR . 'subdir' . DIRECTORY_SEPARATOR . 'mock2.json' => $mockData2,
        ];
        $this->assertEquals($expectedMocksDataForDispatcher, $this->fakeDispatcherFactory->getCreateCallArgs(0)[0], "DispatcherFactory::create not called with correct mocks data");

        $this->assertInstanceOf(Dispatcher::class, $watcher->getDispatcher());
    }

    public function testInitHandlesInvalidJsonGracefully(): void
    {
        $validMockData = [['request' => ['url' => '/valid']]];
        $this->createMockFile('valid.json', \json_encode($validMockData));
        $this->createMockFile('invalid.json', 'this is not json');

        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $this->logger);
        $watcher->init();

        $this->assertSame(1, $this->fakeDispatcherFactory->getCreateCallCount());
        $expectedMocksDataForDispatcher = [
            $this->watchDir . DIRECTORY_SEPARATOR . 'valid.json' => $validMockData,
        ];
        $this->assertEquals($expectedMocksDataForDispatcher, $this->fakeDispatcherFactory->getCreateCallArgs(0)[0]);
    }

    public function testInitHandlesBadRouteExceptionDuringDispatcherCreation(): void
    {
        $this->createMockFile('bad_route.json', \json_encode([
            ['request' => ['url' => '/bad', 'method' => ['INVALID METHOD']]],
        ]));

        $this->fakeDispatcherFactory->throwExceptionOnCreate = new BadRouteException('Test BadRouteException');

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with($this->isInstanceOf(BadRouteException::class));

        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $loggerMock);
        $watcher->init();

        $this->assertSame(1, $this->fakeDispatcherFactory->getCreateCallCount());
        $reflection = new \ReflectionClass(Watcher::class);
        $dispatcherProp = $reflection->getProperty('dispatcher');
        $this->assertFalse($dispatcherProp->isInitialized($watcher), "Dispatcher property should not be initialized after BadRouteException");
    }

    #[DataProvider('fileFilterDataProvider')]
    public function testGetFileFilter(string $filePath, bool $expectedResult, string $message): void
    {
        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, new NullLogger());
        $reflection = new \ReflectionClass(Watcher::class);
        $method = $reflection->getMethod('getFileFilter');
        $method->setAccessible(true);

        $actualResult = $method->invokeArgs($watcher, [$this->watchDir . DIRECTORY_SEPARATOR . $filePath]);
        $this->assertSame($expectedResult, $actualResult, $message);
    }

    public static function fileFilterDataProvider(): array
    {
        return [
            'valid json' => ['file.json', true, 'Regular json file should pass'],
            'valid json in subdir' => ['subdir/file.json', true, 'Json in subdirectory should pass'],
            'hidden json' => ['.file.json', false, 'Hidden json file should not pass'],
            'not json' => ['file.txt', false, 'Non-json file should not pass'],
            'json in ignored dir starting with __' => ['__data/file.json', false, 'Json in __data directory should not pass'],
            'json in nested ignored dir' => ['subdir/__cache/file.json', false, 'Json in nested ignored directory should not pass'],
            'json with __ in name' => ['__file.json', true, 'Json with __ in filename (not directory) should pass'],
            'no extension' => ['file', false, 'File with no extension should not pass'],
        ];
    }

    public function testOnSetFileUpdatesMocksAndSetsFlashMocks(): void
    {
        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $this->logger);
        $watcher->init();

        $newMockData = [['request' => ['url' => '/new'], 'response' => ['text' => 'New']]];
        $filePath = $this->createMockFile('new_mock.json', \json_encode($newMockData));

        $reflection = new \ReflectionClass(Watcher::class);
        $mocksDataProp = $reflection->getProperty('mocksData');
        $flashMocksProp = $reflection->getProperty('flashMocks');
        $flashMocksProp->setAccessible(true);

        $onSetFileMethod = $reflection->getMethod('onSetFile');
        $onSetFileMethod->setAccessible(true);

        $flashMocksProp->setValue($watcher, false);
        $onSetFileMethod->invokeArgs($watcher, ['Create ' . $filePath, $filePath]);

        $this->assertTrue($flashMocksProp->getValue($watcher), 'flashMocks should be true after onSetFile');
        $updatedMocksData = $mocksDataProp->getValue($watcher);
        $this->assertArrayHasKey($filePath, $updatedMocksData);
        $this->assertEquals($newMockData, $updatedMocksData[$filePath]);
    }

    public function testOnDeleteFileRemovesMockAndSetsFlashMocks(): void
    {
        $mockData = [['request' => ['url' => '/to_delete'], 'response' => ['text' => 'Delete Me']]];
        $filePath = $this->createMockFile('delete_me.json', \json_encode($mockData));

        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $this->logger);
        $watcher->init();

        $reflection = new \ReflectionClass(Watcher::class);
        $mocksDataProp = $reflection->getProperty('mocksData');
        $flashMocksProp = $reflection->getProperty('flashMocks');
        $flashMocksProp->setAccessible(true);

        $onDeleteFileMethod = $reflection->getMethod('onDeleteFile');
        $onDeleteFileMethod->setAccessible(true);

        $initialMocksData = $mocksDataProp->getValue($watcher);
        $this->assertArrayHasKey($filePath, $initialMocksData, 'File should be loaded on init');

        $flashMocksProp->setValue($watcher, false);
        $onDeleteFileMethod->invokeArgs($watcher, ['Delete ' . $filePath, $filePath]);
        if (\file_exists($filePath)) {
            \unlink($filePath);
        }

        $this->assertTrue($flashMocksProp->getValue($watcher), 'flashMocks should be true after onDeleteFile');
        $updatedMocksData = $mocksDataProp->getValue($watcher);
        $this->assertArrayNotHasKey($filePath, $updatedMocksData, 'Mock data should be deleted');
    }

    public function testOnSetFileWithInvalidJsonLogsErrorAndDoesNotSetFlashMocks(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with($this->callback(function ($exception) {
                return $exception instanceof \JsonException && \str_contains($exception->getMessage(), 'Syntax error');
            }));

        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $loggerMock);
        $watcher->init();

        $filePath = $this->createMockFile('invalid_on_set.json', 'not json {');

        $reflection = new \ReflectionClass(Watcher::class);
        $flashMocksProp = $reflection->getProperty('flashMocks');
        $flashMocksProp->setAccessible(true);
        $onSetFileMethod = $reflection->getMethod('onSetFile');
        $onSetFileMethod->setAccessible(true);

        $flashMocksProp->setValue($watcher, false);
        $onSetFileMethod->invokeArgs($watcher, ['Create ' . $filePath, $filePath]);

        $this->assertFalse($flashMocksProp->getValue($watcher), 'flashMocks should remain false if parsing error occurred in onSetFile');
    }

    public function testInitWatcherAssignsAndConfiguresFileWatcher(): void
    {
        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $this->logger);
        $fakeFileWatcher = new FakeFileWatcher();

        $reflection = new \ReflectionClass(Watcher::class);
        $initWatcherMethod = $reflection->getMethod('initWatcher');
        $initWatcherMethod->setAccessible(true);

        $watcherProp = $reflection->getProperty('watcher');
        $watcherProp->setAccessible(true);

        $initWatcherMethod->invokeArgs($watcher, [$fakeFileWatcher, $this->watchDir]);

        $this->assertSame($fakeFileWatcher, $watcherProp->getValue($watcher), "Watcher property should be assigned the provided FileWatcher.");
        $this->assertEquals(3, $fakeFileWatcher->onCallCount, "FileWatcher::on() should be called 3 times.");
        $this->assertEquals(1, $fakeFileWatcher->withFilterCallCount, "FileWatcher::withFilter() should be called once.");
        $this->assertEquals(1, $fakeFileWatcher->watchDirsCallCount, "FileWatcher::watchDirs() should be called once.");
        $this->assertNotEmpty($fakeFileWatcher->watchedDirectories, "Watched directories should not be empty.");
        $this->assertContains($this->watchDir, $fakeFileWatcher->watchedDirectories, "Watched directories should contain the main watch directory.");
        $this->assertIsCallable($fakeFileWatcher->appliedFilter, "A filter should be applied to FileWatcher.");
    }

    public function testReadWatcherCallsWatcherRead(): void
    {
        $fakeFileWatcher = new FakeFileWatcher();
        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $this->logger);

        $reflectionWatcher = new \ReflectionClass(Watcher::class);
        $watcherProp = $reflectionWatcher->getProperty('watcher');
        $watcherProp->setAccessible(true);
        $watcherProp->setValue($watcher, $fakeFileWatcher);

        $readWatcherMethod = $reflectionWatcher->getMethod('readWatcher');
        $readWatcherMethod->setAccessible(true);

        $readWatcherMethod->invoke($watcher);

        $this->assertEquals(1, $fakeFileWatcher->readCallCount, "FileWatcher::read() should be called once by readWatcher().");
    }

    public function testRunLoopSimulationHandlesFlashMocksCorrectly(): void
    {
        $fakeFileWatcher = new FakeFileWatcher();
        $watcherSUT = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $this->logger);

        $reflection = new \ReflectionClass(Watcher::class);
        $initWatcherMethod = $reflection->getMethod('initWatcher');
        $initWatcherMethod->setAccessible(true);
        $initWatcherMethod->invokeArgs($watcherSUT, [$fakeFileWatcher, $this->watchDir]);

        $flashMocksProp = $reflection->getProperty('flashMocks');
        $flashMocksProp->setAccessible(true);
        $flashMocksProp->setValue($watcherSUT, true);

        $delayCallCount = 0;
        $maxDelayCalls = 1;

        $delayClosure = function () use (&$delayCallCount, $maxDelayCalls) {
            $delayCallCount++;
            if ($delayCallCount >= $maxDelayCalls) {
                throw new \RuntimeException("Test loop break after {$maxDelayCalls} iteration(s)");
            }
        };

        $initialDispatcherFactoryCallCount = $this->fakeDispatcherFactory->getCreateCallCount();

        try {
            $watcherSUT->run($fakeFileWatcher, $delayClosure);
        } catch (\RuntimeException $e) {
            if (!\str_contains($e->getMessage(), "Test loop break")) {
                throw $e;
            }
        }

        $this->assertEquals($maxDelayCalls, $delayCallCount, "Delay closure should have been called {$maxDelayCalls} time(s).");

        $this->assertEquals($initialDispatcherFactoryCallCount + 1, $this->fakeDispatcherFactory->getCreateCallCount(), "initDispatcher (via dispatcherFactory.create) should be called when flashMocks is true.");

        $this->assertFalse($flashMocksProp->getValue($watcherSUT), "flashMocks should be reset to false after initDispatcher call in run loop.");
    }

    #[DataProvider('eventHandlerDataProvider')]
    public function testInitWatcherEventHandlersCallCorrectMethods(int $eventMaskToSimulate, string $expectedMethodToAffect, bool $isFileCreated): void
    {
        $watcher = new Watcher($this->fakeDispatcherFactory, $this->watchDir, $this->logger);
        $fakeFileWatcher = new FakeFileWatcher();

        $reflection = new \ReflectionClass(Watcher::class);
        $initWatcherMethod = $reflection->getMethod('initWatcher');
        $initWatcherMethod->setAccessible(true);
        $initWatcherMethod->invokeArgs($watcher, [$fakeFileWatcher, $this->watchDir]);

        $flashMocksProp = $reflection->getProperty('flashMocks');
        $flashMocksProp->setAccessible(true);
        $mocksDataProp = $reflection->getProperty('mocksData');
        $mocksDataProp->setAccessible(true);

        $testFileName = 'event_handler_test_file.json';
        $testPath = $this->watchDir . DIRECTORY_SEPARATOR . $testFileName;
        $mockEvent = new Event(0, $eventMaskToSimulate, 0, $testFileName, $testPath);

        if ($isFileCreated) {
            $this->createMockFile($testFileName, \json_encode(['data' => 'sample']));
        } else {
            $mocksDataProp->setValue($watcher, [$testPath => ['data' => 'old_data']]);
        }

        $flashMocksProp->setValue($watcher, false);

        $handlerFoundAndCalled = false;
        foreach ($fakeFileWatcher->registeredHandlers as $mask => $handler) {
            if (($mask & $eventMaskToSimulate) === $eventMaskToSimulate) {
                $handler->call($watcher, $mockEvent);
                $handlerFoundAndCalled = true;
                break;
            }
        }

        $this->assertTrue($handlerFoundAndCalled, "Handler for mask {$eventMaskToSimulate} was not found or not triggered.");
        $this->assertTrue($flashMocksProp->getValue($watcher), "flashMocks should be true after event handler execution.");

        if ($expectedMethodToAffect === 'onSetFile' && $isFileCreated) {
            $currentMocksData = $mocksDataProp->getValue($watcher);
            $this->assertArrayHasKey($testPath, $currentMocksData, "mocksData should contain the file after onSetFile call.");
            $this->assertEquals(['data' => 'sample'], $currentMocksData[$testPath], "mocksData content is incorrect after onSetFile.");
        } elseif ($expectedMethodToAffect === 'onDeleteFile' && !$isFileCreated) {
            $currentMocksData = $mocksDataProp->getValue($watcher);
            $this->assertArrayNotHasKey($testPath, $currentMocksData, "mocksData should not contain the file after onDeleteFile call.");
        }

        if (\file_exists($testPath)) {
            \unlink($testPath);
        }
    }

    public static function eventHandlerDataProvider(): array
    {
        return [
            'create event (onSetFile)' => [IN_CREATE, 'onSetFile', true],
            'moved_to event (onSetFile)' => [IN_MOVED_TO, 'onSetFile', true],
            'delete event (onDeleteFile)' => [IN_DELETE, 'onDeleteFile', false],
            'moved_from event (onDeleteFile)' => [IN_MOVED_FROM, 'onDeleteFile', false],
            'close_write event (onSetFile)' => [IN_CLOSE_WRITE, 'onSetFile', true],
        ];
    }
}

/**
 * Fake implementation of ExternalFileWatcherInterface for testing purposes.
 */
class FakeFileWatcher implements ExternalFileWatcherInterface
{
    /** @var array<int, \Closure> */
    public array $registeredHandlers = [];

    public \Closure|null $appliedFilter = null;
    /** @var string[] */
    public array $watchedDirectories = [];

    public int $onCallCount = 0;

    public int $withFilterCallCount = 0;

    public int $watchDirsCallCount = 0;

    public int $readCallCount = 0;

    public function on(int $mask, \Closure $handler): self
    {
        $this->onCallCount++;
        $this->registeredHandlers[$mask] = $handler;
        return $this;
    }

    public function withFilter(\Closure $filter): self
    {
        $this->withFilterCallCount++;
        $this->appliedFilter = $filter;
        return $this;
    }

    public function watchDirs(array|string $target): self
    {
        $this->watchDirsCallCount++;
        $this->watchedDirectories = \is_array($target) ? $target : [$target];
        return $this;
    }

    public function read(array $params = [], \Closure|null $throwException = null): void
    {
        $this->readCallCount++;
    }
}

/**
 * Fake implementation of DispatcherFactoryInterface for testing purposes.
 */
class FakeDispatcherFactory implements DispatcherFactoryInterface
{
    /** @var array<array{0: iterable}> */
    private array $createCallsArgs = [];

    public \Exception|null $throwExceptionOnCreate = null;

    public function create(iterable $data): Dispatcher
    {
        $this->createCallsArgs[] = [$data];
        if ($this->throwExceptionOnCreate) {
            throw $this->throwExceptionOnCreate;
        }
        return simpleDispatcher(function (RouteCollector $r) use ($data) {
            foreach ($data as $filePath => $fileMocks) {
                if (\is_array($fileMocks)) {
                    foreach ($fileMocks as $mockEntry) {
                        if (\is_array($mockEntry) && isset($mockEntry['request']['url'])) {
                            $r->addRoute(
                                $mockEntry['request']['method'] ?? 'GET',
                                $mockEntry['request']['url'],
                                'fake_handler_for_' . $filePath,
                            );
                        }
                    }
                }
            }
        });
    }

    public function getCreateCallCount(): int
    {
        return \count($this->createCallsArgs);
    }

    public function getCreateCallArgs(int $index): array|null
    {
        return $this->createCallsArgs[$index] ?? null;
    }
}
