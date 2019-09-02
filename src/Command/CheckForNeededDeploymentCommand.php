<?php
/**
 * Command that checks if we need to run database stuff on the current deploy
 */

namespace Graviton\DeploymentServiceBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Graviton\DeploymentServiceBundle\Document\Deployment;
use Jean85\PrettyVersions;
use Jean85\Version;
use PackageVersions\Versions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author  List of contributors <https://github.com/libgraviton/DeploymentServiceBundle/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 */
class CheckForNeededDeploymentCommand extends Command
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var string
     */
    private $selfPackageName;

    /**
     * Constructor.
     *
     * @param DocumentManager $dm              document manager
     * @param string          $selfPackageName self package name
     */
    public function __construct(DocumentManager $dm, string $selfPackageName)
    {
        $this->dm = $dm;
        $this->selfPackageName = $selfPackageName;
        parent::__construct(null);
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('graviton:check-deployment')
            ->setDescription(
                'Checks if for the current commit hash it is needed to run external on-startup deployment things'
            );
    }

    /**
     * {@inheritDoc}
     *
     * @param InputInterface  $input  input
     * @param OutputInterface $output output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // sleep random amount of time between 0.5 and 2s
        $randsleep = mt_rand(500, 2000);
        usleep($randsleep * 1000);
        
        $repo = $this->dm->getRepository(Deployment::class);
        $currentVersion = PrettyVersions::getVersion($this->selfPackageName);
        $currentCommitHash = $currentVersion->getCommitHash();
        
        // something there for current hash?
        $existing = $repo->findOneBy([
            'packageName' => $this->selfPackageName,
            'commitHash' => $currentCommitHash
        ]);

        if (!is_null($existing)) {
            // say no -> nothing needs to be done!
            echo 'NO';
            return;
        }

        $deployment = new Deployment();
        $deployment->setPackageName($this->selfPackageName);
        $deployment->setCommitHash($currentCommitHash);
        $deployment->setCreatedAt(new \DateTime());

        $this->dm->persist($deployment);
        $this->dm->flush();

        echo 'YES';
    }
}
