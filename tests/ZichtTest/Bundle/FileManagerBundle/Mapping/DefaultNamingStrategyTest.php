<?php
/**
 * @author Muhammed Akbulut <muhammed@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Mapping;

use Zicht\Bundle\FileManagerBundle\Mapping\DefaultNamingStrategy;

class DefaultNamingStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testLowerCaseStrategy()
    {
        // Arrange
        $namingStrategy = new DefaultNamingStrategy(DefaultNamingStrategy::LOWER_CASE);

        $given = [
            0 => 'Spaced And Cased Document.docx',
            1 => 'studlyCasedDocument.jpg',
            2 => 'CamelCasedDocument.jpg',
            3 => 'underscored_document.jpg',
            4 => 'overly----striped---document.jpg',
        ];

        $expected = [
            0 => 'spaced-and-cased-document.docx',
            1 => 'studlycaseddocument.jpg',
            2 => 'camelcaseddocument.jpg',
            3 => 'underscored_document.jpg',
            4 => 'overly-striped-document.jpg',
        ];

        $result = array();

        // Act
        foreach ($given as $key => $value) {
            $result[$key] = $namingStrategy->normalize($value);
        }

        // Assert
        foreach ($result as $key => $value) {
            $this->assertEquals($expected[$key], $value);
        }
    }

    public function testLowerCaseNonWordCharStrategy()
    {
        // Arrange
        $namingStrategy = new DefaultNamingStrategy(DefaultNamingStrategy::LOWER_CASE);

        $given = [
            0 => 'Spaced And Cased Document!@#$%^&*()...docx',
            1 => 'studlyCasedDocument!@#$%^&*().jpg',
            2 => 'CamelCasedDocument!@#$%^&*().jpg',
            3 => 'underscored_document(*&^%$#@.jpg',
            4 => 'overly-!@#$%^&--striped---document!@#$%^&*.jpg',
        ];

        $expected = [
            0 => 'spaced-and-cased-document-...docx',
            1 => 'studlycaseddocument-.jpg',
            2 => 'camelcaseddocument-.jpg',
            3 => 'underscored_document-.jpg',
            4 => 'overly-striped-document-.jpg',
        ];

        $result = array();

        // Act
        foreach ($given as $key => $value) {
            $result[$key] = $namingStrategy->normalize($value);
        }

        // Assert
        foreach ($result as $key => $value) {
            $this->assertEquals($expected[$key], $value);
        }
    }

    public function testPreserveCaseStrategy()
    {
        // Arrange
        $namingStrategy = new DefaultNamingStrategy(DefaultNamingStrategy::PRESERVE_CASE);

        $given = [
            0 => 'Spaced And Cased Document.docx',
            1 => 'studlyCasedDocument.jpg',
            2 => 'CamelCasedDocument.jpg',
            3 => 'underscored_document.jpg',
            4 => 'overly----striped---document.jpg',
        ];

        $expected = [
            0 => 'Spaced-And-Cased-Document.docx',
            1 => 'studlyCasedDocument.jpg',
            2 => 'CamelCasedDocument.jpg',
            3 => 'underscored_document.jpg',
            4 => 'overly-striped-document.jpg',
        ];

        $result = array();

        // Act
        foreach ($given as $key => $value) {
            $result[$key] = $namingStrategy->normalize($value);
        }

        // Assert
        foreach ($result as $key => $value) {
            $this->assertEquals($expected[$key], $value);
        }
    }

    public function testLowerCaseSuffixStrategy()
    {
        // Arrange
        $namingStrategy = new DefaultNamingStrategy(DefaultNamingStrategy::LOWER_CASE);

        $given = 'Spaced And Cased Document.docx';

        $expected = [
            0 => 'spaced-and-cased-document.docx',
            1 => 'spaced-and-cased-document-1.docx',
            2 => 'spaced-and-cased-document-2.docx',
            3 => 'spaced-and-cased-document-3.docx',
            4 => 'spaced-and-cased-document-4.docx',
        ];

        $result = array();

        // Act
        foreach ($expected as $key => $value) {
            $result[$key] = $namingStrategy->normalize($given, $key);
        }

        // Assert
        foreach ($result as $key => $value) {
            $this->assertEquals($expected[$key], $value);
        }
    }
}
