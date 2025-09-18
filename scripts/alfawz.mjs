#!/usr/bin/env node
import { spawn } from 'node:child_process';
import { promises as fs, existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');
const apiDir = path.join(projectRoot, 'apps', 'api');
const webDir = path.join(projectRoot, 'apps', 'web');

function log(message) {
  console.log(message);
}

function run(command, args = [], options = {}) {
  const { cwd, env, stdio, shell, capture = false } = options;
  const finalStdio = capture ? ['ignore', 'pipe', 'pipe'] : stdio ?? 'inherit';
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, {
      cwd,
      env,
      stdio: finalStdio,
      shell: shell ?? (process.platform === 'win32'),
    });

    let stdout = '';
    let stderr = '';

    if (capture && child.stdout) {
      child.stdout.setEncoding('utf8');
      child.stdout.on('data', (chunk) => {
        stdout += chunk;
      });
    }

    if (capture && child.stderr) {
      child.stderr.setEncoding('utf8');
      child.stderr.on('data', (chunk) => {
        stderr += chunk;
      });
    }

    child.on('error', (error) => {
      reject(error);
    });

    child.on('close', (code) => {
      if (code === 0) {
        resolve(capture ? { stdout: stdout.trim(), stderr: stderr.trim() } : undefined);
      } else {
        const error = new Error(`Command failed: ${command} ${args.join(' ')}`);
        error.code = code;
        if (stderr) {
          error.stderr = stderr;
        }
        reject(error);
      }
    });
  });
}

async function commandExists(command) {
  const locator = process.platform === 'win32' ? 'where' : 'which';
  try {
    await run(locator, [command], { stdio: 'ignore', shell: process.platform === 'win32' });
    return true;
  } catch (error) {
    return false;
  }
}

async function checkPrerequisites() {
  const requirements = [
    { command: 'php', label: 'PHP 8.2+' },
    { command: 'composer', label: 'Composer' },
    { command: 'node', label: 'Node.js 20+' },
    { command: 'npm', label: 'npm' },
  ];

  const missing = [];
  for (const requirement of requirements) {
    if (!(await commandExists(requirement.command))) {
      missing.push(requirement.label);
    }
  }

  if (missing.length) {
    throw new Error(`Missing required tools: ${missing.join(', ')}`);
  }

  const phpVersion = await run('php', ['--version'], { capture: true });
  const nodeVersion = await run('node', ['--version'], { capture: true });
  const composerVersion = await run('composer', ['--version'], { capture: true });

  log(`✅ PHP: ${phpVersion.stdout.split('\n')[0]}`);
  log(`✅ Node.js: ${nodeVersion.stdout}`);
  log(`✅ Composer: ${composerVersion.stdout.split('\n')[0]}`);
}

async function ensureEnvFile() {
  const envPath = path.join(apiDir, '.env');
  if (!existsSync(envPath)) {
    const examplePath = path.join(apiDir, '.env.example');
    if (existsSync(examplePath)) {
      await fs.copyFile(examplePath, envPath);
      log('📝 Created apps/api/.env from .env.example');
    }
  }

  if (!existsSync(envPath)) {
    return;
  }

  const envContent = await fs.readFile(envPath, 'utf8');
  const appKeyLine = envContent.split('\n').find((line) => line.startsWith('APP_KEY='));
  const hasAppKey = appKeyLine && appKeyLine.trim() !== 'APP_KEY=';

  if (!hasAppKey) {
    log('🔑 Generating Laravel app key...');
    await run('php', ['artisan', 'key:generate', '--force'], { cwd: apiDir });
  }
}

async function runComposerInstall({ production, verbose }) {
  const args = ['install'];
  if (production) {
    args.push('--no-dev', '--optimize-autoloader');
  }
  if (!verbose) {
    args.push('--quiet');
  }

  log('📦 Installing Laravel dependencies (composer install)...');
  await run('composer', args, { cwd: apiDir });
}

async function runMigrations({ fresh, seed, production }) {
  const args = ['artisan'];
  if (fresh) {
    args.push('migrate:fresh');
    if (seed) {
      args.push('--seed');
    }
  } else {
    args.push('migrate');
  }
  if (production) {
    args.push('--force');
  }

  log('🗄️  Running database migrations...');
  try {
    await run('php', args, { cwd: apiDir });
    log('✅ Database migrations completed');
  } catch (error) {
    log('⚠️  Database migrations failed');
    log(error.message);
  }

  if (!fresh && seed) {
    const seedArgs = ['artisan', 'db:seed'];
    if (production) {
      seedArgs.push('--force');
    }
    log('🌱 Running database seeders...');
    try {
      await run('php', seedArgs, { cwd: apiDir });
      log('✅ Database seeding completed');
    } catch (error) {
      log('⚠️  Database seeding failed');
      log(error.message);
    }
  }
}

async function optimizeLaravel({ production }) {
  const commands = [
    ['artisan', 'config:clear'],
    ['artisan', 'route:clear'],
    ['artisan', 'view:clear'],
  ];

  if (production) {
    commands.push(['artisan', 'config:cache']);
    commands.push(['artisan', 'route:cache']);
    commands.push(['artisan', 'view:cache']);
  }

  log('🔧 Optimizing Laravel configuration...');
  for (const command of commands) {
    await run('php', command, { cwd: apiDir });
  }
  log('✅ Laravel optimization completed');
}

async function mirrorDirectory(source, destination) {
  await fs.rm(destination, { recursive: true, force: true });
  await fs.mkdir(destination, { recursive: true });
  await fs.cp(source, destination, { recursive: true });
}

async function buildWeb({ verbose }) {
  log('🌐 Building Next.js web application...');
  const installArgs = ['install'];
  if (!verbose) {
    installArgs.push('--silent');
  }
  await run('npm', installArgs, { cwd: webDir });
  await run('npm', ['run', 'build'], { cwd: webDir });
  await run('npm', ['run', 'export'], { cwd: webDir });

  const outDir = path.join(webDir, 'out');
  const destDir = path.join(apiDir, 'public', 'app');
  await mirrorDirectory(outDir, destDir);
  log('✅ Static export copied to apps/api/public/app');
}

async function ensureStorageDirectories() {
  const storageDirs = [
    path.join(apiDir, 'storage', 'logs'),
    path.join(apiDir, 'storage', 'framework', 'cache'),
    path.join(apiDir, 'storage', 'framework', 'sessions'),
    path.join(apiDir, 'storage', 'framework', 'views'),
    path.join(apiDir, 'bootstrap', 'cache'),
  ];

  for (const dir of storageDirs) {
    await fs.mkdir(dir, { recursive: true });
  }
  log('✅ Ensured Laravel storage directories exist');
}

async function runHealthChecks() {
  log('🏥 Running health checks...');
  try {
    await run('php', ['artisan', '--version'], { cwd: apiDir });
    log('✅ Laravel artisan responded');
  } catch (error) {
    log('⚠️  Laravel artisan check failed');
  }

  try {
    await run('php', ['artisan', 'migrate:status'], { cwd: apiDir });
    log('✅ Database connection looks healthy');
  } catch (error) {
    log('⚠️  Database status check failed');
  }
}

async function deploy(options) {
  const start = Date.now();
  await checkPrerequisites();
  await runComposerInstall(options);
  await ensureEnvFile();
  if (!options.skipMigrations) {
    await runMigrations(options);
  } else {
    log('⏭️  Skipping database migrations');
  }
  await optimizeLaravel(options);
  if (!options.skipWeb) {
    await buildWeb(options);
  } else {
    log('⏭️  Skipping Next.js build');
  }
  await ensureStorageDirectories();
  await runHealthChecks();

  const seconds = ((Date.now() - start) / 1000).toFixed(2);
  log(`🎉 Deployment completed in ${seconds}s`);
}

function printHelp() {
  log(`Usage: node scripts/alfawz.mjs <command> [options]\n`);
  log('Commands:');
  log('  build-web        Build the Next.js project and mirror to Laravel public/app');
  log('  deploy           Run the full deployment pipeline');
  log('  migrate          Run database migrations with --force');
  log('  --help           Show this message');
  log('\nDeploy options:');
  log('  --fresh          Use php artisan migrate:fresh');
  log('  --seed           Seed the database after migrating');
  log('  --skip-web       Do not build the Next.js app');
  log('  --skip-migrations  Skip running database migrations');
  log('  --production     Run production optimizations (composer --no-dev, --force migrations)');
  log('  --verbose        Show verbose npm/composer output');
}

function parseDeployOptions(args) {
  const options = {
    fresh: false,
    seed: false,
    skipWeb: false,
    skipMigrations: false,
    production: false,
    verbose: false,
  };

  for (const arg of args) {
    switch (arg) {
      case '--fresh':
        options.fresh = true;
        break;
      case '--seed':
        options.seed = true;
        break;
      case '--skip-web':
        options.skipWeb = true;
        break;
      case '--skip-migrations':
        options.skipMigrations = true;
        break;
      case '--production':
        options.production = true;
        break;
      case '--verbose':
        options.verbose = true;
        break;
      default:
        throw new Error(`Unknown option: ${arg}`);
    }
  }

  return options;
}

async function migrate() {
  log('🗄️  Running database migrations (php artisan migrate --force)...');
  await run('php', ['artisan', 'migrate', '--force'], { cwd: apiDir });
  log('✅ Database migrations finished');
}

async function main() {
  const [, , command, ...args] = process.argv;

  if (!command || command === '--help' || command === '-h') {
    printHelp();
    process.exit(command ? 0 : 1);
  }

  try {
    switch (command) {
      case 'build-web':
        await buildWeb({ verbose: args.includes('--verbose') });
        break;
      case 'deploy': {
        const options = parseDeployOptions(args);
        await deploy(options);
        break;
      }
      case 'migrate':
        await migrate();
        break;
      default:
        throw new Error(`Unknown command: ${command}`);
    }
  } catch (error) {
    if (error.stderr) {
      log(error.stderr);
    }
    console.error(`❌ ${error.message}`);
    process.exit(1);
  }
}

await main();
