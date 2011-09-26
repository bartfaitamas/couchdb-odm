<?php
namespace Doctrine\Tests\ODM\CouchDB\Functional;

use Doctrine\Tests\Models\Embedded\Embedded;
use Doctrine\Tests\Models\Embedded\Embedder;

use Doctrine\Tests\Models\Embedded\Account;
use Doctrine\Tests\Models\Embedded\Address;
use Doctrine\Tests\Models\Embedded\EmailAccount;
use Doctrine\Tests\Models\Embedded\IMAccount;
use Doctrine\Tests\Models\Embedded\Person;
use Doctrine\Tests\Models\Embedded\PhoneNumber;



class EmbedManyTest extends \Doctrine\Tests\ODM\CouchDB\CouchDBFunctionalTestCase
{
    private $dm;

    public function setUp() 
    {
        $this->type = 'Doctrine\Tests\Models\Embedded\Embedder';
        $this->embeddedType = 'Doctrine\Tests\Models\Embedded\Embedded';

        $this->personType = 'Doctrine\Tests\Models\Embedded\Person';
        $this->addressType = 'Doctrine\Tests\Models\Embedded\Address';
        $this->accountType = 'Doctrine\Tests\Models\Embedded\Account';
        $this->emailAccountType = 'Doctrine\Tests\Models\Embedded\EmailAccount';
        $this->imAccountType = 'Doctrine\Tests\Models\Embedded\IMAccount';
        $this->phoneNumberType = 'Doctrine\Tests\Models\Embedded\PhoneNumber';

        $this->dm = $this->createDocumentManager();

        
        $httpClient = $this->dm->getHttpClient();

        $data = json_encode(
            array('_id' => '1',
                  'type' => str_replace('\\', '.', $this->personType),
                  'name' => 'John Doe',
                  'address' => array(
                      'type'         => str_replace('\\', '.', $this->addressType),
                      'country'      => 'HU',
                      'zip'          => '1234',
                      'city'         => 'Budapest',
                      'street'       => 'Main street 1.',
                      'phoneNumbers' => array(
                          array(
                              'type'         => str_replace('\\', '.', $this->phoneNumberType),
                              'number'       => '003615551234'
                              ),
                          array(
                              'type'         => str_replace('\\', '.', $this->phoneNumberType),
                              'number'       => '003615551235'
                              )
                          ),
                      ),
                  'accounts' => array(
                      array(
                          'type'         => str_replace('\\', '.', $this->imAccountType),
                          'accountName'  => 'doe@gtalk',
                          'identifier'   => 'johndoe@g_mail.com'
                          ),
                      array(
                          'type'         => str_replace('\\', '.', $this->emailAccountType),
                          'accountName'  => 'doe@g_mail.com',
                          'emailAddress' => 'johndoe@g_mail.com'
                          )
                      )

                ));

        $resp = $httpClient->request('PUT', '/' . $this->dm->getDatabase() . '/1', $data);
        $this->assertEquals(201, $resp->status);
    }

    public function testFind()
    {
        $person = $this->dm->find($this->personType, 1);
        $this->assertInstanceOf($this->personType, $person);
        $this->assertEquals(2, count($person->accounts));
        $this->assertEquals('doe@gtalk', $person->accounts[0]->accountName);
        $this->assertEquals('doe@g_mail.com', $person->accounts[1]->accountName);
        
        $this->assertEquals('HU', $person->address->country);
        $this->assertEquals('003615551234', $person->address->phoneNumbers[0]->number);
        $this->assertEquals('003615551235', $person->address->phoneNumbers[1]->number);

        $this->assertInstanceOf($this->imAccountType, $person->accounts[0]);
        $this->assertInstanceOf($this->emailAccountType, $person->accounts[1]);
    }

    public function testShouldNotSaveUnchanged()
    {
        $listener = new PreUpdateSubscriber;
        $this->dm->getEventManager()->addEventListener('preUpdate', $listener);

        $person = $this->dm->find($this->personType, 1);
        $this->dm->flush();

        $this->assertEquals(0, count($listener->eventArgs));
    }

    public function testSave()
    {
        $person = $this->dm->find($this->personType, 1);
        // change the first element
        $person->accounts[0]->accountName = 'changed 1';
        // add another one
        $newAccount = new IMAccount;
        $newAccount->accountName = 'new one';
        $newAccount->identifier = 'new one id';
        $person->accounts[] = $newAccount;
        $this->dm->flush();
        $this->dm->clear();

        $person = $this->dm->find($this->personType, 1);
        $this->assertEquals(3, count($person->accounts));
        $this->assertEquals('new one', $person->accounts[2]->accountName);

        $person->accounts[0]->accountName = 'changed';
        $person->name = 'foo';
        $person->address->phoneNumbers[0]->number = '0';
        $person->address->country = 'ES';
        $this->dm->flush();
        $this->dm->clear();

        $person = $this->dm->find($this->personType, 1);
        $this->assertEquals(3, count($person->accounts));
        $this->assertEquals('foo', $person->name);
        $this->assertEquals('changed', $person->accounts[0]->accountName);
        $this->assertEquals('0', $person->address->phoneNumbers[0]->number);
        $this->assertEquals('ES', $person->address->country);
        
    }

    public function testCreate()
    {
        $newOne = new Person;
        $newOne->id = '2';
        
        $address = new Address;
        $address->country = 'HU';
        
        $phone1 = new PhoneNumber;
        $phone1->number = '1';
        $phone2 = new PhoneNumber;
        $phone2->number = '2';
        $address->phoneNumbers = array($phone1, $phone2);

        $newOne->address = $address;

        $account1 = new EmailAccount;
        $account1->accountName = 'email';
        $account1->emailAddress = 'foo@bar.com';
        $account2 = new IMAccount;
        $account2->accountName = 'im';
        $account2->identifier = 'im-id';
        $newOne->accounts = array($account1, $account2);

        $this->dm->persist($newOne);
        $this->dm->flush();
        $this->dm->clear();

        $newOne = null;
        $this->assertNull($newOne);
        $newOne = $this->dm->find($this->personType, 2);
        $this->assertNotNull($newOne);
        $this->assertEquals(2, count($newOne->accounts));
        $this->assertEquals(2, count($newOne->address->phoneNumbers));

        $this->assertInstanceOf('\Doctrine\Tests\Models\Embedded\EmailAccount', 
                                $newOne->accounts[0]);
        $this->assertInstanceOf('\Doctrine\Tests\Models\Embedded\IMAccount', 
                                $newOne->accounts[1]);

        $this->assertEquals('email', $newOne->accounts[0]->accountName);
        $this->assertEquals('foo@bar.com', $newOne->accounts[0]->emailAddress);
        $this->assertEquals('im', $newOne->accounts[1]->accountName);
        $this->assertEquals('im-id', $newOne->accounts[1]->identifier);
    }

    public function testAssocCreate()
    {
        $person = new Person;
        $person->id = '2';

        $account1 = new EmailAccount;
        $account1->accountName = 'email';
        $account1->emailAddress = 'foo@bar.com';
        $account2 = new IMAccount;
        $account2->accountName = 'im';
        $account2->identifier = 'im-id';
        $person->accounts = array('email' => $account1, 'im' => $account2);
        
        $this->dm->persist($person);
        $this->dm->flush();
        $this->dm->clear();

        $person = null;
        $this->assertNull($person);
        $person = $this->dm->find($this->personType, 2);
        $this->assertNotNull($person);
        $this->assertEquals(2, count($person->accounts));
        $this->assertEquals('email', $person->accounts['email']->accountName);
        $this->assertEquals('im', $person->accounts['im']->accountName);
    }

    public function testMetadataMapping()
    {
        $metadata = $this->dm->getClassMetadata($this->type);
        $this->assertArrayHasKey('embeds', $metadata->fieldMappings);
        $mapping = $metadata->fieldMappings['embeds'];
        $this->assertEquals('mixed', $mapping['type']);
        $this->assertEquals('many', $mapping['embedded']);
        $this->assertEquals($this->embeddedType, $mapping['targetDocument']);
    }

    // TODO testEmbeddedWithNonMappedData
}


