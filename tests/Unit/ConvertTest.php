<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use App\Convert;

class ConvertTest extends TestCase
{
    public function SetUp()
    {
        $options = getopt('', 
            [
                'filename:',
                'output::',
                'format::',
                'addmeta::',
                'flatten::',
                'indexes::',
                'version::',
                'help::'
            ]
        );

        $xml = $this->loadData();
        $directory = [
            'data' => [
                 'valid.xml' => $xml,
                 'invalid.xml' => ''
            ],
            'output' => []
        ];
        // setup and cache the virtual file system
        $this->file_system = vfsStream::setup('root', 444, $directory);

        $this->convert = new Convert([$options]);
    }

    /**
     * @test
     */
    public function set_and_get_options_functions()
    {
        $name = 'add_meta';
        $value = [$name => 'set to true'];
        $this->convert->setOption($name, $value);
        $this->assertEquals($this->convert->getOption($name), $value['add_meta']);
    }

    public function test_set_option_true()
    {
        $this->convert->setOption('flatten', ['flatten' => true]);
        $this->assertEquals(true, $this->convert->getOption('flatten'));
    }

    public function test_leave_boolean_option_as_default()
    {
        $this->assertEquals(false, $this->convert->getOption('addmeta'));
    }

    /**
     * @test
     */
    public function all_arguments_properly_set()
    {
        $options = [
            'filename' => '/my/file/name.xml',
            'output' => 'newoutputfolder',
            'format' => 'testformat',
            'addmeta' => true,
            'flatten' => true,
            'indexes' => true,
        ];

        $this->convert->setArguments($options);

        // Assert that each argument is actually set correct
        $this->assertEquals($this->convert->getOption('filename'), $options['filename']);
    }

    /**
     * @test
     */
    public function file_meta_data_gets_properly_built()
    {
        $options = [
            'title' => 'My file title',
            'url' => '/my/url'
        ];

        // Test with add_meta disabled
        $this->convert->setOption('addmeta', ['addmeta' => null]);        
        $metadata = $this->convert->getMetaData($options);
        $this->assertEquals('', $metadata);

        // Test with add_meta enabled
        $this->convert->setOption('addmeta', ['addmeta' => true]);        
        $metadata = $this->convert->getMetaData($options);
        $this->assertEquals($metadata, "---\ntitle: My file title\npermalink: //my/url/\n---\n\n");
    }

    public function test_cleantext_normalizes_path()
    {
        $data = $this->helper_loadXMLData();
        $fileMeta = $this->convert->retrieveFileInfo($data[1]->xpath('title'));
        $cleanText = $this->convert->cleanText('[[../../minutes|can be found here]]', $fileMeta);
        $this->assertEquals('[[minutes|can be found here]]', $cleanText);
    }

    public function test_cleantext_fixed_relative_path()
    {
        $data = $this->helper_loadXMLData();
        $fileMeta = $this->convert->retrieveFileInfo($data[1]->xpath('title'));
        $cleanText = $this->convert->cleanText('[[/minutes|can be found here]]', $fileMeta);
        $this->assertEquals('[[Folderone/Pagetwo/minutes|can be found here]]', $cleanText);
    }

    public function test_fixes_commonly_broken_external_links()
    {
        $data = $this->helper_loadXMLData();
        $fileMeta = $this->convert->retrieveFileInfo($data[1]->xpath('title'));
        $cleanText = $this->convert->cleanText('[[https://minutes can be found here]]', $fileMeta);
        $this->assertEquals('[https://minutes can be found here]', $cleanText);
    }
    /**
     * @test
     */
    public function retrieveFileInfo_builds_single_name_variables_properly()
    {
        $data = $this->helper_loadXMLData();
        $fileMeta = $this->convert->retrieveFileInfo($data[0]->xpath('title'));
        $validData = [
          "directory"=> "output/",
          "filename"=> "Pageone",
          "title" => "Pageone",
          "url" => "Pageone"
        ];
        $this->assertEquals($fileMeta, $validData);
    }

    /**
     * @test
     */
    public function retrieveFileInfo_builds_multi_name_variables_properly()
    {
        $data = $this->helper_loadXMLData();
        $fileMeta = $this->convert->retrieveFileInfo($data[1]->xpath('title'));
        $validData = [
            'directory' => 'output/Folderone/',
            'filename' => 'Pagetwo',
            'title' => 'Folderone Pagetwo',
            'url' => 'Folderone/Pagetwo'
        ];
        $this->assertEquals($fileMeta, $validData);
    }

    /**
     * @test
     */
    public function valid_xml_when_loading_valid_xml_file()
    {
        $data = $this->helper_loadXMLData();
        $this->assertEquals($data[0]->title, 'Pageone');
        $this->assertEquals($data[1]->title, 'Folderone/Pagetwo');
        $this->assertEquals(count($data), 2);
    }

    /**
     * @expectedException Exception
     */ 
    public function test_exception_thrown_when_loading_invalid_xml_file()
    {
        $this->convert->setOption('filename', ['filename' => $this->file_system->url() . '/data/invalid.xml']);
        $this->convert->loadData( $this->convert->loadFile());
    }

     /**
     * @expectedException Exception
     */ 
    public function test_exception_thrown_when_loading_data_from_none_existant_file()
   {
        $this->convert->setOption('filename', ['filename' => $this->file_system->url() . '/data/nonexistantfile.xml']);
        $this->convert->loadFile();
    }

    /**
     * @test
     */ 
    public function test_file_exists()
    {
        $file = $this->file_system->url() . '/data/valid.xml';
        $this->convert->setOption('filename', ['filename' => $file]);
        $this->assertFileExists($file);
    }

    /**
     * @expectedException Exception
     */ 
    public function test_exception_thrown_when_create_directory_fails()
    {
        // Missing newFolder slash, creates an error
        $newFolder = $this->file_system->url() . 'nonexistantoutput';
        $this->convert->setOption('filename', ['filename' => $newFolder]);
        $this->convert->createDirectory($newFolder);
    }

    /**
     * @test
     */
    public function test_no_errors_when_create_directory_valid()
    {
        $newFolder = $this->file_system->url() . '/output_to_create';
        $this->convert->createDirectory($newFolder);
        $this->assertFileExists($newFolder);
    }

    /**
     * @test
     */
    public function get_current_version()
    {
        $this->expectOutputRegex('/Version.*/');
        $this->convert->getVersion();
    }
    /**
     * @test
     */
    public function help_is_loadable()
    {   
        $this->expectOutputRegex('/.*MIT License.*/');
        $this->convert->help();
    }

    private function helper_loadXMLData()
    {
        $this->convert->setOption('filename', ['filename' => $this->file_system->url() . '/data/valid.xml']);
        $this->convert->loadData( $this->convert->loadFile());
        return $this->convert->getOption('dataToConvert');
    }

    private function loadData() {
        return <<<XMLFILE
<mediawiki xmlns="http://www.mediawiki.org/xml/export-0.10/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.mediawiki.org/xml/export-0.10/ http://www.mediawiki.org/xml/export-0.10.xsd" version="0.10" xml:lang="en">
  <siteinfo>
    <sitename>CompanyWiki</sitename>
    <dbname>company_wiki</dbname>
    <base>https://domain.com/path/to/wiki/Main_Page</base>
    <generator>MediaWiki 1.29.1</generator>
    <case>first-letter</case>
    <namespaces>
      <namespace key="-2" case="first-letter">Media</namespace>
    </namespaces>
  </siteinfo>
  <page>
    <title>Pageone</title>
    <ns>0</ns>
    <id>3145</id>
    <revision>
      <id>40821</id>
      <parentid>40578</parentid>
      <timestamp>2016-11-10T21:37:26Z</timestamp>
      <contributor>
        <username>Kbr google</username>
        <id>758</id>
      </contributor>
      <comment>/* Technical Documents */</comment>
      <model>wikitext</model>
      <format>text/x-wiki</format>
      <text xml:space="preserve" bytes="23787">__TOC__

This is a page

== Documents ==

* [[Folderone/Documentone|A document that needs a link]]
* [[Foldertwo/Documenttwo|A document that also needs a link]]

* First line of list
** First line of indented list
* Second line of list
** Second line of indented list

== A heading ==

Follow this link to [http://example.com/path/to/destination/ see where it goes].

== Bad Links to fix ==

* [[https://example.com/this/is/external/ Improperly formatted link]]
* Link that breaks older pandoc: [https://example.com/script.php?search=findme&amp;parttwo=&amp;three=.&amp;four=ansdwer this link should be fixed now]
        </text>
      <sha1>hymbf8qh3k49td4dg7qdfsox1er4xpw</sha1>
    </revision>
  </page>
  <page>
    <title>Folderone/Pagetwo</title>
    <ns>0</ns>
    <id>3406</id>
    <revision>
      <id>29360</id>
      <parentid>29283</parentid>
      <timestamp>2010-08-04T21:11:57Z</timestamp>
      <contributor>
        <username>Cmarrin</username>
        <id>744</id>
      </contributor>
      <minor/>
      <comment>[[Directory/SubDirectory]] moved to [[Directory/OtherDirectory]]: New Name</comment>
      <model>wikitext</model>
      <format>text/x-wiki</format>
      <text xml:space="preserve" bytes="2536">=== Attendance ===

* name - company
* name2 - company2
* name3 - company3

=== Agenda ===


name: name2 are you here now?
  - no sure if I am here or there.

- next meeting is in two years
  - I think I can have the updates completed by then

=== Audio ===

[http://domain.com/recording/file.mp3 Audio Recording]
      </text>
      <sha1>0blsvabcwdureue00oaytv56hr84j4r</sha1>
    </revision>
  </page>
</mediawiki>
XMLFILE;

    }
}
