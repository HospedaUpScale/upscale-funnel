<?php

require_once __DIR__ . '/../src/autoload.php';
require_once __DIR__ . '/../tests/TestCase.php';

$testDirs = [
    __DIR__ . '/../tests/Unit',
    __DIR__ . '/../tests/Integration',
];

$testFiles = [];
foreach ($testDirs as $dir) {
    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            if (str_ends_with($file, 'Test.php')) {
                $testFiles[] = $dir . '/' . $file;
            }
        }
    }
}

echo "\n\033[1;36m=====================================================\033[0m\n";
echo "\033[1;36m       upScale Automated Test Suite (TDD)            \033[0m\n";
echo "\033[1;36m=====================================================\033[0m\n\n";

if (empty($testFiles)) {
    echo "\033[0;33mNo test files found in tests/Unit or tests/Integration.\033[0m\n\n";
    exit(0);
}

$totalTests = 0;
$totalAssertions = 0;
$failures = [];
$startTime = microtime(true);

foreach ($testFiles as $file) {
    require_once $file;
    $className = pathinfo($file, PATHINFO_FILENAME);
    
    // Find class with namespace or global
    $fullClass = "Tests\\Unit\\{$className}";
    if (!class_exists($fullClass)) {
        $fullClass = "Tests\\Integration\\{$className}";
    }
    if (!class_exists($fullClass)) {
        $fullClass = $className;
    }

    if (!class_exists($fullClass) || !is_subclass_of($fullClass, Tests\TestCase::class)) {
        continue;
    }

    $reflector = new ReflectionClass($fullClass);
    $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);
    
    echo "\033[1m" . $className . "\033[0m\n";

    foreach ($methods as $method) {
        if (!str_starts_with($method->name, 'test')) {
            continue;
        }

        $totalTests++;
        /** @var Tests\TestCase $instance */
        $instance = new $fullClass();
        
        try {
            $instance->runTest($method->name);
            $totalAssertions += $instance->getAssertionsCount();
            echo "  \033[0;32m✓\033[0m {$method->name}\n";
        } catch (\Throwable $e) {
            $failures[] = [
                'class' => $className,
                'method' => $method->name,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            echo "  \033[0;31m✗\033[0m {$method->name}\n";
        }
    }
    echo "\n";
}

$elapsed = round((microtime(true) - $startTime) * 1000, 2);

echo "\033[1;36m-----------------------------------------------------\033[0m\n";
if (empty($failures)) {
    echo "\033[0;32mPASS: {$totalTests} tests, {$totalAssertions} assertions in {$elapsed}ms\033[0m\n\n";
    exit(0);
} else {
    echo "\033[0;31mFAILURES (" . count($failures) . "):\033[0m\n";
    foreach ($failures as $f) {
        echo "  - {$f['class']}::{$f['method']}: {$f['message']} ({$f['file']}:{$f['line']})\n";
    }
    echo "\n\033[0;31mFAILED: " . count($failures) . " of {$totalTests} tests failed.\033[0m\n\n";
    exit(1);
}
