<?php

namespace RokkaCli\Command;

use Rokka\Client\Core\SourceImageCollection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImageUploadCommand extends BaseRokkaCliCommand
{
    protected function configure()
    {
        $this
            ->setName('image:upload')
            ->setDescription('Upload a given image to Rokka')
            ->addArgument('image-file', InputArgument::REQUIRED, 'The image file to upload')
            ->addOption('organization', null, InputOption::VALUE_REQUIRED, 'Specify the Organization to upload the image to.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $organization = $this->configuration->getOrganizationName($input->getOption('organization'));

        if (!$this->verifyOrganizationName($organization, $output)) {
            return -1;
        }

        if (!$this->verifyOrganizationExists($organization, $output)) {
            return -1;
        }

        $image = $input->getArgument('image-file');
        if (!$this->verifyLocalImageExists($image, $output)) {
            return -1;
        }

        $imageClient = $this->getImageClient();
        $contents = file_get_contents($image);
        $binaryHash = sha1_file($image);

        $output->write('Uploading image: <info>'.$image.'</info> to <comment>'.$organization.'</comment> ...');

        $ret = $imageClient->uploadSourceImage($contents, basename($image), $organization);
        if ($ret instanceof SourceImageCollection && $ret->count() == 1) {
            // We, at least, uploaded the image correctly. Check the binary hash to confirm it.
            $sourceImage = $ret->getSourceImages()[0];
            if ($sourceImage->binaryHash != $binaryHash) {
                $output->writeln($this->formatterHelper->formatBlock([
                    'Error!',
                    'The image has been uploaded to Rokka, but the source and uploaded hashes does not match!',
                ], 'error', true));

                return -1;
            }

            $output->writeln(' <info>Done</info>');
            $output->writeln('');

            self::outputImageInfo($sourceImage, $output);
        }

        return 0;
    }
}
