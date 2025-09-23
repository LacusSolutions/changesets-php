<?php

declare(strict_types=1);

namespace Lacus\Changesets\Git;

use Symfony\Component\Process\Process;

final class GitOperations
{
    private string $workingDirectory;

    public function __construct(string $workingDirectory = '.')
    {
        $this->workingDirectory = realpath($workingDirectory) ?: $workingDirectory;
    }

    public function isGitRepository(): bool
    {
        return is_dir($this->workingDirectory . '/.git');
    }

    public function getCurrentBranch(): string
    {
        $process = $this->runGitCommand(['rev-parse', '--abbrev-ref', 'HEAD']);
        return trim($process->getOutput());
    }

    public function getCurrentCommit(): string
    {
        $process = $this->runGitCommand(['rev-parse', 'HEAD']);
        return trim($process->getOutput());
    }

    public function getShortCommit(string $commit = 'HEAD'): string
    {
        $process = $this->runGitCommand(['rev-parse', '--short', $commit]);
        return trim($process->getOutput());
    }

    public function getCommitMessage(string $commit = 'HEAD'): string
    {
        $process = $this->runGitCommand(['log', '-1', '--pretty=%B', $commit]);
        return trim($process->getOutput());
    }

    public function getCommitAuthor(string $commit = 'HEAD'): string
    {
        $process = $this->runGitCommand(['log', '-1', '--pretty=%an <%ae>', $commit]);
        return trim($process->getOutput());
    }

    public function getCommitDate(string $commit = 'HEAD'): string
    {
        $process = $this->runGitCommand(['log', '-1', '--pretty=%ci', $commit]);
        return trim($process->getOutput());
    }

    public function getChangedFiles(string $from = 'HEAD~1', string $to = 'HEAD'): array
    {
        $process = $this->runGitCommand(['diff', '--name-only', $from, $to]);
        $output = trim($process->getOutput());

        if (empty($output)) {
            return [];
        }

        return explode("\n", $output);
    }

    public function getChangedFilesInDirectory(string $directory, string $from = 'HEAD~1', string $to = 'HEAD'): array
    {
        $allFiles = $this->getChangedFiles($from, $to);
        $directory = rtrim($directory, '/') . '/';

        return array_filter($allFiles, function (string $file) use ($directory) {
            return str_starts_with($file, $directory);
        });
    }

    public function hasUncommittedChanges(): bool
    {
        $process = $this->runGitCommand(['status', '--porcelain']);
        return !empty(trim($process->getOutput()));
    }

    public function getUncommittedFiles(): array
    {
        $process = $this->runGitCommand(['status', '--porcelain']);
        $output = trim($process->getOutput());

        if (empty($output)) {
            return [];
        }

        $files = [];
        foreach (explode("\n", $output) as $line) {
            $status = substr($line, 0, 2);
            $file = trim(substr($line, 3));
            $files[] = [
                'status' => $status,
                'file' => $file,
            ];
        }

        return $files;
    }

    public function addFile(string $file): void
    {
        $this->runGitCommand(['add', $file]);
    }

    public function addAll(): void
    {
        $this->runGitCommand(['add', '.']);
    }

    public function commit(string $message, array $files = []): string
    {
        if (!empty($files)) {
            foreach ($files as $file) {
                $this->addFile($file);
            }
        }

        $process = $this->runGitCommand(['commit', '-m', $message]);
        return $this->getCurrentCommit();
    }

    public function createTag(string $tag, string $message = ''): void
    {
        $args = ['tag'];
        if (!empty($message)) {
            $args[] = '-a';
            $args[] = '-m';
            $args[] = $message;
        }
        $args[] = $tag;

        $this->runGitCommand($args);
    }

    public function deleteTag(string $tag): void
    {
        $this->runGitCommand(['tag', '-d', $tag]);
    }

    public function getTags(): array
    {
        $process = $this->runGitCommand(['tag', '--sort=-version:refname']);
        $output = trim($process->getOutput());

        if (empty($output)) {
            return [];
        }

        return explode("\n", $output);
    }

    public function getTagForCommit(string $commit = 'HEAD'): ?string
    {
        $process = $this->runGitCommand(['describe', '--tags', '--exact-match', $commit]);

        if (!$process->isSuccessful()) {
            return null;
        }

        return trim($process->getOutput());
    }

    public function getCommitsSince(string $since): array
    {
        $process = $this->runGitCommand(['log', '--oneline', '--no-merges', "{$since}..HEAD"]);
        $output = trim($process->getOutput());

        if (empty($output)) {
            return [];
        }

        $commits = [];
        foreach (explode("\n", $output) as $line) {
            $parts = explode(' ', $line, 2);
            $commits[] = [
                'hash' => $parts[0],
                'message' => $parts[1] ?? '',
            ];
        }

        return $commits;
    }

    public function getCommitsForFile(string $file, int $limit = 10): array
    {
        $process = $this->runGitCommand(['log', '--oneline', '-n', (string) $limit, '--', $file]);
        $output = trim($process->getOutput());

        if (empty($output)) {
            return [];
        }

        $commits = [];
        foreach (explode("\n", $output) as $line) {
            $parts = explode(' ', $line, 2);
            $commits[] = [
                'hash' => $parts[0],
                'message' => $parts[1] ?? '',
            ];
        }

        return $commits;
    }

    public function checkout(string $branch): void
    {
        $this->runGitCommand(['checkout', $branch]);
    }

    public function createBranch(string $branch, string $from = 'HEAD'): void
    {
        $this->runGitCommand(['checkout', '-b', $branch, $from]);
    }

    public function deleteBranch(string $branch, bool $force = false): void
    {
        $args = ['branch'];
        if ($force) {
            $args[] = '-D';
        } else {
            $args[] = '-d';
        }
        $args[] = $branch;

        $this->runGitCommand($args);
    }

    public function getBranches(): array
    {
        $process = $this->runGitCommand(['branch', '--list']);
        $output = trim($process->getOutput());

        if (empty($output)) {
            return [];
        }

        $branches = [];
        foreach (explode("\n", $output) as $line) {
            $branch = trim(ltrim($line, '* '));
            $branches[] = $branch;
        }

        return $branches;
    }

    public function getRemoteBranches(): array
    {
        $process = $this->runGitCommand(['branch', '-r']);
        $output = trim($process->getOutput());

        if (empty($output)) {
            return [];
        }

        $branches = [];
        foreach (explode("\n", $output) as $line) {
            $branch = trim($line);
            if (!empty($branch) && !str_contains($branch, '->')) {
                $branches[] = $branch;
            }
        }

        return $branches;
    }

    public function getRemotes(): array
    {
        $process = $this->runGitCommand(['remote', '-v']);
        $output = trim($process->getOutput());

        if (empty($output)) {
            return [];
        }

        $remotes = [];
        foreach (explode("\n", $output) as $line) {
            $parts = explode("\t", $line);
            if (count($parts) >= 2) {
                $name = $parts[0];
                $url = explode(' ', $parts[1])[0];
                $remotes[$name] = $url;
            }
        }

        return $remotes;
    }

    public function push(string $remote = 'origin', string $branch = null): void
    {
        $args = ['push', $remote];
        if ($branch !== null) {
            $args[] = $branch;
        }

        $this->runGitCommand($args);
    }

    public function pushTags(string $remote = 'origin'): void
    {
        $this->runGitCommand(['push', $remote, '--tags']);
    }

    public function pull(string $remote = 'origin', string $branch = null): void
    {
        $args = ['pull', $remote];
        if ($branch !== null) {
            $args[] = $branch;
        }

        $this->runGitCommand($args);
    }

    public function fetch(string $remote = 'origin'): void
    {
        $this->runGitCommand(['fetch', $remote]);
    }

    public function isShallowClone(): bool
    {
        return file_exists($this->workingDirectory . '/.git/shallow');
    }

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function setWorkingDirectory(string $workingDirectory): void
    {
        $this->workingDirectory = realpath($workingDirectory) ?: $workingDirectory;
    }

    private function runGitCommand(array $args): Process
    {
        $process = new Process(['git', ...$args], $this->workingDirectory);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new GitException(
                "Git command failed: git " . implode(' ', $args) . "\n" . $process->getErrorOutput()
            );
        }

        return $process;
    }
}
