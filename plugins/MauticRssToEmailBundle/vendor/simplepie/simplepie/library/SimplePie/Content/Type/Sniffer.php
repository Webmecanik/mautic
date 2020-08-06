<?php
/**
 * SimplePie.
 *
 * A PHP-Based RSS and Atom Feed Framework.
 * Takes the hard work out of managing a complete RSS/Atom solution.
 *
 * Copyright (c) 2004-2016, Ryan Parman, Geoffrey Sneddon, Ryan McCue, and contributors
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * 	* Redistributions of source code must retain the above copyright notice, this list of
 * 	  conditions and the following disclaimer.
 *
 * 	* Redistributions in binary form must reproduce the above copyright notice, this list
 * 	  of conditions and the following disclaimer in the documentation and/or other materials
 * 	  provided with the distribution.
 *
 * 	* Neither the name of the SimplePie Team nor the names of its contributors may be used
 * 	  to endorse or promote products derived from this software without specific prior
 * 	  written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS
 * OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS
 * AND CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
 * OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright 2004-2016 Ryan Parman, Geoffrey Sneddon, Ryan McCue
 * @author Ryan Parman
 * @author Geoffrey Sneddon
 * @author Ryan McCue
 *
 * @see http://simplepie.org/ SimplePie
 *
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * Content-type sniffing.
 *
 * Based on the rules in http://tools.ietf.org/html/draft-abarth-mime-sniff-06
 *
 * This is used since we can't always trust Content-Type headers, and is based
 * upon the HTML5 parsing rules.
 *
 *
 * This class can be overloaded with {@see SimplePie::set_content_type_sniffer_class()}
 */
class SimplePie_Content_Type_Sniffer
{
    /**
     * File object.
     *
     * @var SimplePie_File
     */
    public $file;

    /**
     * Create an instance of the class with the input file.
     *
     * @param SimplePie_Content_Type_Sniffer $file Input file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Get the Content-Type of the specified file.
     *
     * @return string Actual Content-Type
     */
    public function get_type()
    {
        if (isset($this->file->headers['content-type'])) {
            if (!isset($this->file->headers['content-encoding'])
                && ('text/plain' === $this->file->headers['content-type']
                    || 'text/plain; charset=ISO-8859-1' === $this->file->headers['content-type']
                    || 'text/plain; charset=iso-8859-1' === $this->file->headers['content-type']
                    || 'text/plain; charset=UTF-8' === $this->file->headers['content-type'])) {
                return $this->text_or_binary();
            }

            if (false !== ($pos = strpos($this->file->headers['content-type'], ';'))) {
                $official = substr($this->file->headers['content-type'], 0, $pos);
            } else {
                $official = $this->file->headers['content-type'];
            }
            $official = trim(strtolower($official));

            if ('unknown/unknown' === $official
                || 'application/unknown' === $official) {
                return $this->unknown();
            } elseif ('+xml' === substr($official, -4)
                || 'text/xml' === $official
                || 'application/xml' === $official) {
                return $official;
            } elseif ('image/' === substr($official, 0, 6)) {
                if ($return = $this->image()) {
                    return $return;
                }

                return $official;
            } elseif ('text/html' === $official) {
                return $this->feed_or_html();
            }

            return $official;
        }

        return $this->unknown();
    }

    /**
     * Sniff text or binary.
     *
     * @return string Actual Content-Type
     */
    public function text_or_binary()
    {
        if ("\xFE\xFF" === substr($this->file->body, 0, 2)
            || "\xFF\xFE" === substr($this->file->body, 0, 2)
            || "\x00\x00\xFE\xFF" === substr($this->file->body, 0, 4)
            || "\xEF\xBB\xBF" === substr($this->file->body, 0, 3)) {
            return 'text/plain';
        } elseif (preg_match('/[\x00-\x08\x0E-\x1A\x1C-\x1F]/', $this->file->body)) {
            return 'application/octect-stream';
        }

        return 'text/plain';
    }

    /**
     * Sniff unknown.
     *
     * @return string Actual Content-Type
     */
    public function unknown()
    {
        $ws = strspn($this->file->body, "\x09\x0A\x0B\x0C\x0D\x20");
        if ('<!doctype html' === strtolower(substr($this->file->body, $ws, 14))
            || '<html' === strtolower(substr($this->file->body, $ws, 5))
            || '<script' === strtolower(substr($this->file->body, $ws, 7))) {
            return 'text/html';
        } elseif ('%PDF-' === substr($this->file->body, 0, 5)) {
            return 'application/pdf';
        } elseif ('%!PS-Adobe-' === substr($this->file->body, 0, 11)) {
            return 'application/postscript';
        } elseif ('GIF87a' === substr($this->file->body, 0, 6)
            || 'GIF89a' === substr($this->file->body, 0, 6)) {
            return 'image/gif';
        } elseif ("\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" === substr($this->file->body, 0, 8)) {
            return 'image/png';
        } elseif ("\xFF\xD8\xFF" === substr($this->file->body, 0, 3)) {
            return 'image/jpeg';
        } elseif ("\x42\x4D" === substr($this->file->body, 0, 2)) {
            return 'image/bmp';
        } elseif ("\x00\x00\x01\x00" === substr($this->file->body, 0, 4)) {
            return 'image/vnd.microsoft.icon';
        }

        return $this->text_or_binary();
    }

    /**
     * Sniff images.
     *
     * @return string Actual Content-Type
     */
    public function image()
    {
        if ('GIF87a' === substr($this->file->body, 0, 6)
            || 'GIF89a' === substr($this->file->body, 0, 6)) {
            return 'image/gif';
        } elseif ("\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" === substr($this->file->body, 0, 8)) {
            return 'image/png';
        } elseif ("\xFF\xD8\xFF" === substr($this->file->body, 0, 3)) {
            return 'image/jpeg';
        } elseif ("\x42\x4D" === substr($this->file->body, 0, 2)) {
            return 'image/bmp';
        } elseif ("\x00\x00\x01\x00" === substr($this->file->body, 0, 4)) {
            return 'image/vnd.microsoft.icon';
        }

        return false;
    }

    /**
     * Sniff HTML.
     *
     * @return string Actual Content-Type
     */
    public function feed_or_html()
    {
        $len = strlen($this->file->body);
        $pos = strspn($this->file->body, "\x09\x0A\x0D\x20\xEF\xBB\xBF");

        while ($pos < $len) {
            switch ($this->file->body[$pos]) {
                case "\x09":
                case "\x0A":
                case "\x0D":
                case "\x20":
                    $pos += strspn($this->file->body, "\x09\x0A\x0D\x20", $pos);
                    continue 2;

                case '<':
                    $pos++;
                    break;

                default:
                    return 'text/html';
            }

            if ('!--' === substr($this->file->body, $pos, 3)) {
                $pos += 3;
                if ($pos < $len && false !== ($pos = strpos($this->file->body, '-->', $pos))) {
                    $pos += 3;
                } else {
                    return 'text/html';
                }
            } elseif ('!' === substr($this->file->body, $pos, 1)) {
                if ($pos < $len && false !== ($pos = strpos($this->file->body, '>', $pos))) {
                    ++$pos;
                } else {
                    return 'text/html';
                }
            } elseif ('?' === substr($this->file->body, $pos, 1)) {
                if ($pos < $len && false !== ($pos = strpos($this->file->body, '?>', $pos))) {
                    $pos += 2;
                } else {
                    return 'text/html';
                }
            } elseif ('rss' === substr($this->file->body, $pos, 3)
                || 'rdf:RDF' === substr($this->file->body, $pos, 7)) {
                return 'application/rss+xml';
            } elseif ('feed' === substr($this->file->body, $pos, 4)) {
                return 'application/atom+xml';
            } else {
                return 'text/html';
            }
        }

        return 'text/html';
    }
}
