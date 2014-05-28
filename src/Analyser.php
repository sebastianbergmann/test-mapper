<?php
/**
 * test-mapper
 *
 * Copyright (c) 2014, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author    Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright 2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 1.0.0
 */

namespace SebastianBergmann\TestMapper;

use PHP_Token_Stream;

/**
 * @author    Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright 2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link      http://github.com/sebastianbergmann/test-mapper/tree
 * @since     Class available since Release 1.0.0
 */
class Analyser
{
    /**
     * @param array $files
     * @return array
     */
    public function analyse(array $files)
    {
        $data = array(
            'tests' => array(),
            'units' => array()
        );

        foreach ($files as $file) {
            $ts = new PHP_Token_Stream($file);

            foreach ($ts->getClasses() as $className => $classData) {
                $data['tests'][$className] = array();

                foreach ($classData['methods'] as $methodName => $methodData) {
                    $annotations = $this->parseDocblock($methodData['docblock']);

                    $data['tests'][$className][$methodName] = array(
                        'covers' => $annotations['covers'],
                        'uses'   => $annotations['uses']
                    );

                    $data['units'] = array_merge(
                        $data['units'],
                        $annotations['covers'],
                        $annotations['uses']
                    );
                }
            }
        }

        $data['units'] = array_unique($data['units']);
        sort($data['units']);

        return $data;
    }

    /**
     * @param string $docblock
     * @return array
     */
    private function parseDocblock($docblock)
    {
        $annotations = array('covers' => array(), 'uses' => array());
        $docblock    = substr($docblock, 3, -2);

        if (preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $docblock, $matches)) {
            $numMatches = count($matches[0]);

            for ($i = 0; $i < $numMatches; ++$i) {
                $annotations[$matches['name'][$i]][] = $matches['value'][$i];
            }
        }

        array_walk_recursive(
            $annotations,
            function (&$element) {
                if (substr($element, 0, 1) == '\\') {
                    $element = substr($element, 1);
                }
            }
        );

        return $this->filterAnnotations($annotations);
    }

    /**
     * @param  array $annotations
     * @return array
     * @since  Method available since Release 1.0.1
     */
    private function filterAnnotations(array $annotations)
    {
        return array(
            'covers' => array_filter(
                $annotations['covers'],
                array($this, 'filterElement')
            ),
            'uses' => array_filter(
                $annotations['uses'],
                array($this, 'filterElement')
            ),
        );
    }

    /**
     * @param  string $element
     * @return boolean
     * @since  Method available since Release 1.0.1
     */
    private function filterElement($element)
    {
        $tmp = strpos($element, '::');

        // @covers ClassName
        // @covers ::functionName
        if ($tmp === false || $tmp === 0) {
            return false;
        }

        // @covers ClassName<extended>
        // @covers ClassName::<public>
        // @covers ClassName::<protected>
        // @covers ClassName::<private>
        // @covers ClassName::<!public>
        // @covers ClassName::<!protected>
        // @covers ClassName::<!private>
        if (strpos($element, '<') !== false) {
            return false;
        }

        return true;
    }
}
