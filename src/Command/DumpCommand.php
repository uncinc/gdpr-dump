<?php

namespace machbarmacher\GdprDump\Command;

use Ifsnop\Mysqldump\Mysqldump;
use machbarmacher\GdprDump\ConfigParser;
use machbarmacher\GdprDump\MysqldumpGdpr;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Console command for dumping a MySQL database with optional GDPR sanitization.
 */
class DumpCommand extends Command {

  /**
   * Configures the command options and arguments.
   */
  protected function configure() {
    $this
      ->setName('mysqldump')
      ->setDescription('Dump a mysql database, with optionally sanitizing private data. See https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html')
      ->addArgument('db-name', InputArgument::REQUIRED, 'DB name.')
      ->addArgument('include-tables',
              InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
              'Only include these tables, include all if empty')
      ->addOption('display-effective-replacements', NULL,
              InputOption::VALUE_NONE,
              'If this option is specified, gdpr-dump simply outputs the effective replacements that will be done to the data if the dump is done')
      ->addOption('ignore-table', NULL,
              InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
              'Do not dump specified table. Use multiple times for multiple tables. Table should be specified with both database and table name (i.e. database.tablename)')
      ->addOption('result-file', 'r', InputOption::VALUE_OPTIONAL,
              'Implies --add-locks --disable-keys --extended-insert --hex-blob --no-autocommit --single-transaction.')
      ->addOption('user', 'u', InputOption::VALUE_OPTIONAL,
              'The connection user name.')
      ->addOption('password', 'p', InputOption::VALUE_OPTIONAL,
              'The connection password.', FALSE)
      ->addOption('host', NULL, InputOption::VALUE_OPTIONAL,
              'The connection host name.')
      ->addOption('port', 'P', InputOption::VALUE_OPTIONAL,
              'The connection port number.')
      ->addOption('socket', 's', InputOption::VALUE_OPTIONAL,
              'The connection socket.')
      ->addOption('db-type', NULL, InputOption::VALUE_OPTIONAL,
              'The connection DB type. Options are: mysql (default), pgsql, sqlite, dblib.',
              'mysql')
      ->addOption('defaults-file', NULL, InputOption::VALUE_OPTIONAL,
              'An additional my.cnf file.')
      ->addOption('compress-result-file', NULL, InputOption::VALUE_OPTIONAL,
              'Compress resulting file, available Options: Gzip, Bzip2. Defaults to None.', 'None')
      ->addOption('compress', 'C', InputOption::VALUE_NONE,
              'Compress all information sent between the client and the server if both support compression.')
      ->addOption('init_commands', NULL,
              InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
              'DB Init commands.')
      ->addOption('no-data', NULL, InputOption::VALUE_NONE,
              'Do not dump table contents.')
      ->addOption('reset-auto-increment', NULL, InputOption::VALUE_NONE,
              'Removes the AUTO_INCREMENT option from the database definition. Useful when used with no-data, so when db is recreated, it will start from 1 instead of using an old value.')
      ->addOption('add-drop-database', NULL, InputOption::VALUE_NONE,
              'Write a DROP DATABASE statement before each CREATE DATABASE statement. ')
      ->addOption('add-drop-table', NULL, InputOption::VALUE_NONE,
              'Write a DROP TABLE statement before each CREATE TABLE statement.')
      ->addOption('add-drop-trigger', NULL, InputOption::VALUE_NONE,
              'Write a DROP TRIGGER statement before each CREATE TRIGGER statement.')
      ->addOption('add-locks', NULL, InputOption::VALUE_NONE,
              'Surround each table dump with LOCK TABLES and UNLOCK TABLES statements. This results in faster inserts when the dump file is reloaded.')
      ->addOption('complete-insert', NULL, InputOption::VALUE_NONE,
              'Use complete INSERT statements that include column names.')
      ->addOption('default-character-set', NULL,
              InputOption::VALUE_OPTIONAL,
              'Default charset. Defaults to utf8mb4.', 'utf8mb4')
      ->addOption('disable-keys', NULL, InputOption::VALUE_NONE,
              'Adds disable-keys statements for faster dump execution. Defaults to on, use no-disable-keys to switch off.')
      ->addOption('extended-insert', 'e', InputOption::VALUE_NONE,
              'Write INSERT statements using multiple-row syntax that includes several VALUES lists. This results in a smaller dump file and speeds up inserts when the file is reloaded. Defaults to on, use no-extended-insert to switch off.')
      ->addOption('events', NULL, InputOption::VALUE_NONE,
              'Dump events from dumped databases	')
      ->addOption('hex-blob', NULL, InputOption::VALUE_NONE,
              'Dump binary columns using hexadecimal notation.')
      ->addOption('net_buffer_length', NULL, InputOption::VALUE_OPTIONAL,
              'Buffer size for TCP/IP and socket communication	')
      ->addOption('no-autocommit', NULL, InputOption::VALUE_NONE,
              'Enclose the INSERT statements for each dumped table within SET autocommit = 0 and COMMIT statements.')
      ->addOption('no-create-info', NULL, InputOption::VALUE_NONE,
              'Do not write CREATE DATABASE statements.')
      ->addOption('lock-tables', 'l', InputOption::VALUE_NONE,
              'Lock all tables before dumping them.')
      ->addOption('routines', NULL, InputOption::VALUE_NONE,
              'Dump stored routines (procedures and functions) from dumped databases.')
      ->addOption('single-transaction', NULL, InputOption::VALUE_NONE,
              'Issue a BEGIN SQL statement before dumping data from server.')
      ->addOption('skip-triggers', NULL, InputOption::VALUE_NONE,
              'Do not dump triggers.')
      ->addOption('skip-tz-utc', NULL, InputOption::VALUE_NONE,
              'Turn off tz-utc.')
      ->addOption('skip-comments', NULL, InputOption::VALUE_NONE,
              'Do not add comments to dump file.')
      ->addOption('skip-dump-date', NULL, InputOption::VALUE_NONE,
              'Skip dump date to better compare dumps.')
      ->addOption('skip-definer', NULL, InputOption::VALUE_NONE,
              'Omit DEFINER and SQL SECURITY clauses from the CREATE statements for views and stored programs.')
      ->addOption('where', NULL, InputOption::VALUE_OPTIONAL,
              'Dump only rows selected by given WHERE condition.')
      ->addOption('gdpr-expressions', NULL, InputOption::VALUE_OPTIONAL,
              'A json of gdpr sql-expressions keyed by table and column.')
      ->addOption('gdpr-replacements', NULL, InputOption::VALUE_OPTIONAL,
              'A json of gdpr replacement values keyed by table and column.')
      ->addOption('gdpr-replacements-file', NULL, InputOption::VALUE_OPTIONAL,
              'File that contains a json of gdpr replacement values keyed by table and column.')
      ->addOption('debug-sql', NULL, InputOption::VALUE_NONE,
              'Add a comment with the dump sql.')
      ->addOption('locale', NULL, InputOption::VALUE_OPTIONAL,
          'The wanted locale for the export.')
            // This seems NOT to work as documented.
            // ->addOption('databases', NULL, InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'Dump several databases. Normally, mysqldump treats the first name argument on the command line as a database name and following names as table names. With this option, it treats all name arguments as database names.')
            // Add some options that e.g. drush expects.
      ->addOption('quote-names', 'Q', InputOption::VALUE_NONE,
              'Currently ignored.')
      ->addOption('opt', NULL, InputOption::VALUE_NONE,
              'Implies --add-drop-table --add-locks --disable-keys --extended-insert --hex-blob --no-autocommit --single-transaction.');
  }

  /**
   * Prompts the user to enter a password interactively.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The console input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The console output.
   *
   * @return string
   *   The password entered by the user.
   */
  public function getPasswordFromConsole(
    InputInterface $input,
    OutputInterface $output,
  ) {
    $questionHelper = $this->getHelper('question');
    $question = new Question("Enter password:");
    $question->setHidden(TRUE);
    $question->setHiddenFallback(FALSE);
    $password = $questionHelper->ask($input, $output, $question);
    return $password;
  }

  /**
   * Determines the effective password from console options or user input.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The console input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The console output.
   * @param mixed|null $default
   *   The default password to fall back to.
   *
   * @return mixed|null
   *   The resolved password.
   */
  public function getEffectivePassword(
    InputInterface $input,
    OutputInterface $output,
    $default = NULL,
  ) {
    $password = $default;

    $consolePasswordOption = $input->getOption('password');
    // We have a console password of some sort.
    if ($consolePasswordOption !== FALSE) {
      // We need to ask for user input.
      if ($consolePasswordOption === NULL) {
        $password = $this->getPasswordFromConsole($input, $output);
        // Data has been passed in via the option itself.
      }
      else {
        $password = $consolePasswordOption;
      }
    }

    return $password;
  }

  /**
   * Executes the dump command.
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $dumpSettings =
            $this->getOptOptions($input->getOption('opt'))
            + $this->getDefaults($input->getOption('defaults-file'))
            + array_filter($input->getArguments())
            + array_filter($input->getOptions())
            + array_fill_keys([
              'user',
              'host',
              'port',
              'socket',
              'db-name',
              'db-type',
            ], NULL);
    $user = $dumpSettings['user'];

    $dumpSettings['password'] = $this->getEffectivePassword($input, $output, $dumpSettings['password']);
    $password = $dumpSettings['password'];

    $dsn = $this->getDsn($dumpSettings);

    $dumpSettings['exclude-tables'] = $this->getExcludedTables($dumpSettings);

    if (!empty($dumpSettings['gdpr-expressions'])) {
      $dumpSettings['gdpr-expressions'] = json_decode($dumpSettings['gdpr-expressions'],
            TRUE);
      if (json_last_error()) {
        throw new \UnexpectedValueException(sprintf('Invalid gdpr-expressions json (%s): %s',
          json_last_error_msg(), $dumpSettings['gdpr-expressions']));
      }
    }

    if (!empty($dumpSettings['gdpr-replacements'])) {
      $dumpSettings['gdpr-replacements'] = json_decode($dumpSettings['gdpr-replacements'],
            TRUE);
      if (json_last_error()) {
        throw new \UnexpectedValueException(sprintf('Invalid gdpr-replacements json (%s): %s',
          json_last_error_msg(), $dumpSettings['gdpr-replacements']));
      }
    }

    if (!empty($dumpSettings['gdpr-replacements-file'])) {
      $dumpSettings['gdpr-replacements'] = json_decode(file_get_contents($dumpSettings['gdpr-replacements-file']),
            TRUE);
      if (json_last_error()) {
        throw new \UnexpectedValueException(sprintf('Invalid gdpr-replacements json (%s): %s',
          json_last_error_msg(), $dumpSettings['gdpr-replacements']));
      }
    }

    $pdoSettings = [];

    if (!empty($dumpSettings['compress'])) {
      if ($dumpSettings['db-type'] !== 'mysql') {
        throw new \UnexpectedValueException(sprintf('Option compress is not available for db type %s',
              $dumpSettings['db-type']));
      }
      $pdoSettings[] = \PDO::MYSQL_ATTR_COMPRESS;
      unset($dumpSettings['compress']);
    }

    // Remap mysqldump option to Mysqldump one.
    if (!empty($dumpSettings['compress-result-file'])) {
      $dumpSettings['compress'] = $dumpSettings['compress-result-file'];
    }

    $dumpSettings = array_intersect_key($dumpSettings,
          $this->getDumpSettingsDefault());

    if ($input->getOption('display-effective-replacements')) {
      // We simply display the gdpr-dump specific settings and exit.
      $this->displayEffectiveReplacements($output, $dumpSettings);
    }
    else {
      $dumper = new MysqldumpGdpr($dsn, $user, $password, $dumpSettings,
            $pdoSettings);
      $dumper->start($input->getOption('result-file'));
    }

    return 0;
  }

  /**
   * Returns default settings loaded from my.cnf config files.
   */
  protected function getDefaults($extraFile) {
    $defaultsFiles[] = '/etc/my.cnf';
    $defaultsFiles[] = '/etc/mysql/my.cnf';

    if ($extraFile) {
      $defaultsFiles[] = $extraFile;
    }

    if ($homeDir = getenv('MYSQL_HOME')) {
      $defaultsFiles[] = "$homeDir/.my.cnf";
      $defaultsFiles[] = "$homeDir/.mylogin.cnf";
      $defaultsFiles[] = "$homeDir/.gdpr.cnf";
    }

    if ($gdprDumpHome = getenv('GDPR_DUMP_HOME')) {
      $defaultsFiles[] = "$gdprDumpHome/gdpr.cnf";
      $defaultsFiles[] = "$gdprDumpHome/.gdpr.cnf";
    }

    $config = new ConfigParser();
    foreach ($defaultsFiles as $defaultsFile) {
      if (is_readable($defaultsFile)) {
        $config->addFile($defaultsFile);
      }
    }
    return $config->getFiltered(['client', 'mysqldump']);
  }

  /**
   * Builds the DSN connection string from dump settings.
   */
  protected function getDsn(array $dumpSettings) {
    $dbName = $dumpSettings['db-name'];
    $dbType = $dumpSettings['db-type'];
    $host = $dumpSettings['host'];
    $port = $dumpSettings['port'];
    $socket = $dumpSettings['socket'];
    $dsn = "$dbType:dbname=$dbName";
    if ($host) {
      $dsn .= ";host=$host";
    }
    if ($port) {
      $dsn .= ";port=$port";
    }
    if ($socket) {
      $dsn .= ";unix_socket=$socket";
    }
    return $dsn;
  }

  /**
   * Returns the options implied by the --opt flag.
   */
  protected function getOptOptions($switch) {
    return !$switch ? [] : [
      'add-drop-table' => TRUE,
      'add-locks' => TRUE,
          // --create-options
      'disable-keys' => TRUE,
      'extended-insert' => TRUE,
      'lock-tables' => TRUE,
          // --quick
          // --set-charset
          // --------------
          // 'hex-blob' => TRUE,
          // 'no-autocommit' => TRUE,
          // 'single-transaction' => TRUE,
    ];
  }

  /**
   * Returns the list of excluded table names derived from ignore-table options.
   */
  protected function getExcludedTables(array $dumpSettings) {
    $excludedTables = [];
    if (!empty($dumpSettings['ignore-table']) && is_array($dumpSettings['ignore-table'])) {
      // Mysqldump expects ignore-table values to be in the form database.tablename.
      foreach ($dumpSettings['ignore-table'] as $tableName) {
        if (preg_match("/.+\.(.+)$/u", $tableName, $m)) {
          $excludedTables[] = $m[1];
        }
      }
    }
    return $excludedTables;
  }

  /**
   * Returns the default dump settings array.
   */
  protected function getDumpSettingsDefault() {
    // Literal copy from \Ifsnop\Mysqldump\Mysqldump::__construct.
    return [
      'include-tables' => [],
      'exclude-tables' => [],
      'compress' => Mysqldump::NONE,
      'init_commands' => [],
      'no-data' => [],
      'reset-auto-increment' => FALSE,
      'add-drop-database' => FALSE,
      'add-drop-table' => FALSE,
      'add-drop-trigger' => TRUE,
      'add-locks' => TRUE,
      'complete-insert' => FALSE,
      'databases' => FALSE,
      'default-character-set' => Mysqldump::UTF8,
      'disable-keys' => TRUE,
      'extended-insert' => TRUE,
      'events' => FALSE,
      'hex-blob' => TRUE, /* faster than escaped content */
      'net_buffer_length' => 0,
      'no-autocommit' => TRUE,
      'no-create-info' => FALSE,
      'lock-tables' => TRUE,
      'routines' => FALSE,
      'single-transaction' => TRUE,
      'skip-triggers' => FALSE,
      'skip-tz-utc' => FALSE,
      'skip-comments' => FALSE,
      'skip-dump-date' => FALSE,
      'skip-definer' => FALSE,
      'where' => '',
              /* deprecated */
      'disable-foreign-keys-check' => TRUE,
    ] + [
      'gdpr-expressions' => NULL,
      'gdpr-replacements' => NULL,
      'debug-sql' => FALSE,
      'locale' => "en_US",
    ];
  }

  /**
   * Outputs the effective GDPR replacement settings to the console.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The console output.
   * @param array $dumpSettings
   *   The resolved dump settings.
   */
  protected function displayEffectiveReplacements(
    OutputInterface $output,
    $dumpSettings,
  ) {
    if (isset($dumpSettings['gdpr-expressions'])) {
      $output->writeln("gdpr-expressions=" . json_encode($dumpSettings['gdpr-expressions']));
    }
    if (isset($dumpSettings['gdpr-replacements'])) {
      $output->writeln("gdpr-replacements=" . json_encode($dumpSettings['gdpr-replacements']));
    }
  }

}
