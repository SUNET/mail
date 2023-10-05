<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use JsonException;
use OCA\Mail\AppInfo\Application;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version3500Date20231005091430 extends SimpleMigrationStep {
	/** @var IConfig */
	protected $config;

	/** @var IDBConnection */
	protected $connection;

	/** @var LoggerInterface */
	protected $logger;

	public function __construct(IConfig $config, IDBConnection $connection, LoggerInterface $logger) {
		$this->config = $config;
		$this->connection = $connection;
		$this->logger = $logger;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('mail_provisionings')) {
			$provisioningTable = $schema->getTable('mail_provisionings');
			$provisioningTable->addColumn('master_password_enabled', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
			$provisioningTable->addColumn('master_password', Types::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// Fetch old config
		$raw = $this->config->getAppValue(
			Application::APP_ID,
			'provisioning_settings'
		);
		if ($raw === '') {
			// Not config set yet
			return;
		}

		try {
			$conf = json_decode($raw, true, 10, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			$this->logger->error('Json decode for old provisioning config failed: ' . $e->getMessage() . ' - building manual config', [
				'exception' => $e,
			]);
			// build config manually
			$conf = [];
		}

		// create first entry
		$insertQb = $this->connection->getQueryBuilder();
		$insertQb->insert('mail_provisionings');
		$insertQb->setValue('provisioning_domain', $insertQb->createNamedParameter('*')); // wildcard domain for this provisioning
		$insertQb->setValue('email_template', $insertQb->createNamedParameter($conf['email'] ?? '%USERID%@domain.com'));
		$insertQb->setValue('imap_user', $insertQb->createNamedParameter($conf['imapUser'] ?? '%USERID%@domain.com'));
		$insertQb->setValue('imap_host', $insertQb->createNamedParameter($conf['imapHost'] ?? 'imap.domain.com'));
		$insertQb->setValue('imap_port', $insertQb->createNamedParameter($conf['imapPort'] ?? 993, IQueryBuilder::PARAM_INT));
		$insertQb->setValue('imap_ssl_mode', $insertQb->createNamedParameter($conf['imapSslMode'] ?? 'ssl'));
		$insertQb->setValue('smtp_user', $insertQb->createNamedParameter($conf['smtpUser'] ?? '%USERID%@domain.com'));
		$insertQb->setValue('smtp_host', $insertQb->createNamedParameter($conf['smtpHost'] ?? 'smtp.domain.com'));
		$insertQb->setValue('smtp_port', $insertQb->createNamedParameter($conf['smtpPort'] ?? 587, IQueryBuilder::PARAM_INT));
		$insertQb->setValue('smtp_ssl_mode', $insertQb->createNamedParameter($conf['smtpSslMode'] ?? 'tls'));
		$insertQb->setValue('sieve_enabled', $insertQb->createNamedParameter((bool)($conf['sieveEnabled'] ?? false), IQueryBuilder::PARAM_BOOL));
		$insertQb->setValue('sieve_user', $insertQb->createNamedParameter($conf['sieveUser'] ?? ''));
		$insertQb->setValue('sieve_host', $insertQb->createNamedParameter($conf['sieveHost'] ?? ''));
		$insertQb->setValue('sieve_port', $insertQb->createNamedParameter($conf['sievePort'] ?? 4190, IQueryBuilder::PARAM_INT));
		$insertQb->setValue('sieve_ssl_mode', $insertQb->createNamedParameter($conf['sieveSslMode'] ?? ''));
		$insertQb->executeStatement();
		$id = $insertQb->getLastInsertId();

		// set wildcard provisioning config for all provisioned accounts so we don't use state
		$updateQb = $this->connection->getQueryBuilder();
		$updateQb = $updateQb->update('mail_accounts')
			->set('provisioning_id', $updateQb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			->where($updateQb->expr()->eq('provisioned', $updateQb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));
		$updateQb->executeStatement();

		$this->config->deleteAppValue(
			Application::APP_ID,
			'provisioning_settings'
		);
	}
}