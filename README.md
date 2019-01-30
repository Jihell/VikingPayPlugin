HtmlToPdfBundle
===============

A SF2 bundle to easily handle pdf generation and concatenation with wkhtmltopdf and pdftk
This bundle only provide a php object way to g, basically it will simply execute the command.
If you need some particular feature, you should create your own command abstraction class.


1- Install
----------

Add plugin to your composer.json require:

    {
        "require": {
            "jihel/html-to-pdf-bundle": "dev-master",
        }
    }

or

    php composer.phar require jihel/html-to-pdf-bundle:dev-master

Install packages binaries:

    aptitude install wkhtmltopdf pdftk xvfb

Add bundle to your AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Jihel\Plugin\HtmlToPdfBundle\JihelPluginHtmlToPdfBundle(),
        );
    }


2- Configure your config.yml
----------------------------

The default configuration file and explanations can be found [here](doc/config.md)


3- Usage
--------

Get the generator service **jihel.plugin.html_to_pdf.generator.pdf**:

- From a controller


    /** @var \Jihel\Plugin\HtmlToPdfBundle\Generator\PdfGenerator $pdfGenerator */
    $pdfGenerator = $this->get('jihel.plugin.html_to_pdf.generator.pdf');

- From a service


    service:
        my.super.pdf.service:
            class: %my.super.mdf.service.class%
            arguments: { '@jihel.plugin.html_to_pdf.generator.pdf' }

The class provite two methods, one to generate a pdf from a template name and datas,
and the other to concatenate all pages from pdf files in an array list or a folder.

    /**
     * Create a .pdf file from the given $template name with given $data parameters
     *
     * @param string $template
     * @param array $data
     * @return \SplFileObject
     */
    public function create($template, array $data = array())

    /**
     * Concatenate the given pdf files.
     * If you provide a path a folder,
     * concatenate them by alphabetic order
     *
     * @param \SplFileObject[]|string $files
     * @param bool $unlink
     * @return \SplFileObject
     * @throws Exception\NotASplFileObjectException
     */
    public function concatenate($files, $unlink = false)

Both will return an SplFileObject temporally saved in the /tmp folder.

You can read execution messages with the methods :

    public function getLastConcatenateCommandResult()
    public function getLastCreateCommandResult()

Only the last executed command output will be visible.
Think about the verbose option to have more details.


4- Note
-------

The `use_xvfb` option is resources consuming. Think about using wkhtmltopdf on a server
with a permanent xserver running.

If you want to include background pictures, put the absolute path in your $data array.


5- Thanks
---------

Thanks to Romain Sebille (Give me your email man !) who made the research and the first iteration of the command generator.
Thanks to my cat to keep meowing me.
Thanks to me for giving my free time doing class for lazy developers.
You can access read CV [here](http://www.joseph-lemoine.fr)
