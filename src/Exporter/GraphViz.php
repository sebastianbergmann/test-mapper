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

namespace SebastianBergmann\TestMapper\Exporter;

/**
 * @author    Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright 2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link      http://github.com/sebastianbergmann/test-mapper/tree
 * @since     Class available since Release 1.0.0
 */
class GraphViz
{
    /**
     * @var integer
     */
    private $clusterId = 0;

    /**
     * @param string $filename
     * @param array  $data
     */
    public function export($filename, array $data)
    {
        $buffer = "digraph TestMap {\n";
        $buffer .= "    ranksep=5;\n";
        $buffer .= "    node [shape=box];\n";
        $buffer .= $this->renderTestClusters($data['tests']);
        $buffer .= $this->renderUnitClusters($data['units']);
        $buffer .= $this->renderCoversEdges($data['tests']) . "\n";
        $buffer .= $this->renderUsesEdges($data['tests']);
        $buffer .= "}\n";

        file_put_contents($filename, $buffer);
    }

    /**
     * @param  array $tests
     * @return string
     */
    private function renderCoversEdges(array $tests)
    {
        $buffer = '';

        foreach ($tests as $testClass => $testMethods) {
            foreach ($testMethods as $testMethod => $data) {
                $from = $this->getId($testClass, $testMethod);

                foreach ($data['covers'] as $coveredElement) {
                    list ($class, $method) = explode('::', $coveredElement);

                    $buffer .= sprintf(
                        "    %s -> %s;\n",
                        $from,
                        $this->getId($class, $method)
                    );
                }
            }
        }

        return $buffer;
    }

    /**
     * @param  array $tests
     * @return string
     */
    private function renderUsesEdges(array $tests)
    {
        $buffer = '';

        foreach ($tests as $testClass => $testMethods) {
            foreach ($testMethods as $testMethod => $data) {
                if (count($data['covers']) != 1 || empty($data['uses'])) {
                    continue;
                }

                list ($class, $method) = explode('::', $data['covers'][0]);
                $from = $this->getId($class, $method);

                foreach ($data['uses'] as $usedElement) {
                    list ($class, $method) = explode('::', $usedElement);
                    $to = $this->getId($class, $method);

                    $buffer .= sprintf(
                        "    %s -> %s;\n",
                        $from,
                        $to
                    );
                }
            }
        }

        return $buffer;
    }

    /**
     * @param  array $tests
     * @return string
     */
    private function renderTestClusters(array $tests)
    {
        $buffer = '';

        foreach ($tests as $className => $methods) {
            $buffer .= '    subgraph cluster' . $this->clusterId++ . " {\n";
            $buffer .= '        label="' . str_replace('\\', '\\\\', $className) . '";' . "\n";

            foreach (array_keys($methods) as $methodName) {
                $buffer .= sprintf(
                    "        %s [label=\"%s\"];\n",
                    $this->getId($className, $methodName),
                    $methodName
                );
            }

            $buffer .= "    }\n\n";
        }

        return $buffer;
    }

    /**
     * @param  array $units
     * @return string
     */
    private function renderUnitClusters(array $units)
    {
        $buffer       = '';
        $currentClass = null;

        foreach ($units as $unit) {
            list ($class, $method) = explode('::', $unit);

            if ($class != $currentClass) {
                if ($currentClass != null) {
                    $buffer .= "    }\n\n";
                }

                $buffer .= '    subgraph cluster' . $this->clusterId++ . " {\n";
                $buffer .= '        label="' .str_replace('\\', '\\\\', $class) . '";' . "\n";

                $currentClass = $class;
            }

            $buffer .= sprintf(
                "        %s [label=\"%s\"];\n",
                $this->getId($class, $method),
                $method
            );
        }

        $buffer .= "    }\n\n";

        return $buffer;
    }

    /**
     * @param  string $class
     * @param  string $method
     * @return string
     */
    private function getId($class, $method)
    {
        return strtolower(str_replace('\\', '_', $class . '_' . $method));
    }
}
