<?php

namespace SteadyUa\Unicorn\Cmd;

class FileBackup
{
    private $files = [];

    private function backupPath(string $filePath): string
    {
        return $filePath . '.bak';
    }

    public function addFile(string $filePath): void
    {
        $this->files[$filePath] = $filePath;
    }

    public function backup(): void
    {
        foreach ($this->files as $filePath) {
            $backupPath = $this->backupPath($filePath);
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }
            if (file_exists($filePath)) {
                copy($filePath, $backupPath);
            }
        }
    }

    public function restore(): void
    {
        foreach ($this->files as $filePath) {
            $backupPath = $this->backupPath($filePath);
            if (file_exists($backupPath)) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                rename($backupPath, $filePath);
            }
        }
    }

    public function clean(): void
    {
        foreach ($this->files as $filePath) {
            $backupPath = $this->backupPath($filePath);
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }
        }
    }
}
