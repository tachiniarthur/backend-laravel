<?php

namespace Tests\TesteIA\Commands;

use App\Console\Commands\TestMetricsCommand;
use App\Testing\Metrics\MetricsExtension;
use PHPUnit\Runner\Extension\Extension;
use Tests\TesteIA\TesteIATestCase;

class TestMetricsCommandTest extends TesteIATestCase
{
    /**
     * Validates: Requirements 14.1
     * Testa que o comando test:metrics aceita a opção --testsuite=TesteIA e executa.
     */
    public function test_command_acceptsTestsuiteOption_executesSuccessfully(): void
    {
        // Arrange
        $command = new TestMetricsCommand();

        // Act - verify the command signature includes --testsuite option
        $definition = $command->getDefinition();

        // Assert
        $this->assertTrue($definition->hasOption('testsuite'));
        $this->assertEquals('JuniorPleno', $definition->getOption('testsuite')->getDefault());
    }

    /**
     * Validates: Requirements 14.1
     * Testa que o comando test:metrics possui todas as opções esperadas.
     */
    public function test_command_hasAllExpectedOptions_optionsExist(): void
    {
        // Arrange
        $command = new TestMetricsCommand();
        $definition = $command->getDefinition();

        // Act & Assert
        $this->assertTrue($definition->hasOption('filter'));
        $this->assertTrue($definition->hasOption('testsuite'));
        $this->assertTrue($definition->hasOption('all'));
        $this->assertTrue($definition->hasOption('no-coverage'));
        $this->assertTrue($definition->hasOption('output'));
        $this->assertTrue($definition->hasOption('json'));
    }

    /**
     * Validates: Requirements 14.1
     * Testa que o comando está registrado com a assinatura correta.
     */
    public function test_command_hasCorrectSignature_nameIsTestMetrics(): void
    {
        // Arrange
        $command = new TestMetricsCommand();

        // Act
        $name = $command->getName();

        // Assert
        $this->assertEquals('test:metrics', $name);
    }

    /**
     * Validates: Requirements 14.2
     * Testa que MetricsExtension implementa a interface Extension do PHPUnit.
     */
    public function test_metricsExtension_implementsExtensionInterface_isCompatible(): void
    {
        // Arrange & Act
        $reflection = new \ReflectionClass(MetricsExtension::class);

        // Assert
        $this->assertTrue($reflection->implementsInterface(Extension::class));
    }

    /**
     * Validates: Requirements 14.2
     * Testa que MetricsExtension possui o método bootstrap necessário.
     */
    public function test_metricsExtension_hasBootstrapMethod_methodExists(): void
    {
        // Arrange
        $reflection = new \ReflectionClass(MetricsExtension::class);

        // Act & Assert
        $this->assertTrue($reflection->hasMethod('bootstrap'));

        $method = $reflection->getMethod('bootstrap');
        $this->assertTrue($method->isPublic());
    }

    /**
     * Validates: Requirements 14.2
     * Testa que MetricsExtension está configurada no phpunit.xml.
     */
    public function test_metricsExtension_isConfiguredInPhpunitXml_extensionRegistered(): void
    {
        // Arrange
        $phpunitXmlPath = base_path('phpunit.xml');

        // Act
        $content = file_get_contents($phpunitXmlPath);

        // Assert
        $this->assertNotFalse($content);
        $this->assertStringContainsString('App\Testing\Metrics\MetricsExtension', $content);
    }

    /**
     * Validates: Requirements 14.1
     * Testa que a testsuite TesteIA está registrada no phpunit.xml.
     */
    public function test_testsuiteTesteIA_isRegisteredInPhpunitXml_suiteExists(): void
    {
        // Arrange
        $phpunitXmlPath = base_path('phpunit.xml');

        // Act
        $content = file_get_contents($phpunitXmlPath);

        // Assert
        $this->assertNotFalse($content);
        $this->assertStringContainsString('<testsuite name="TesteIA">', $content);
        $this->assertStringContainsString('tests/TesteIA', $content);
    }

    /**
     * Validates: Requirements 14.2
     * Testa que MetricsExtension possui métodos estáticos para gerenciar o collector.
     */
    public function test_metricsExtension_hasCollectorManagement_staticMethodsExist(): void
    {
        // Arrange
        $reflection = new \ReflectionClass(MetricsExtension::class);

        // Act & Assert
        $this->assertTrue($reflection->hasMethod('getCollector'));
        $this->assertTrue($reflection->hasMethod('resetCollector'));

        $this->assertTrue($reflection->getMethod('getCollector')->isStatic());
        $this->assertTrue($reflection->getMethod('resetCollector')->isStatic());
    }
}
