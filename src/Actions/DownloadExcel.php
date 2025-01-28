<?php

namespace Maatwebsite\LaravelNovaExcel\Actions;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Http\Requests\ActionRequest;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadExcel extends ExportToExcel
{
    /**
     * Get the displayable name of the action.
     *
     * @return string
     */
    public function name()
    {
        return $this->name ?? __('Download Excel');
    }

    /**
     * @param  ActionRequest  $request
     * @param  Action  $exportable
     * @return mixed
     */
    public function handle(ActionRequest $request, Action $exportable)
    {
        if (config('excel.temporary_files.remote_disk')) {
            return $this->handleRemoteDisk($request, $exportable);
        }

        $response = Excel::download(
            $exportable,
            $this->getFilename(),
            $this->getWriterType()
        );

        if (!$response instanceof BinaryFileResponse || $response->isInvalid()) {
            return \is_callable($this->onFailure)
                ? ($this->onFailure)($request, $response)
                : Action::danger(__('Resource could not be exported.'));
        }

        return \is_callable($this->onSuccess)
            ? ($this->onSuccess)($request, $response)
            : Action::downloadURL(
                $this->getFilename(),
                $this->getDownloadUrl($response->getFile()->getPathname())
            );
    }

    /**
     * @param  ActionRequest  $request
     * @param  Action  $exportable
     * @return mixed
     */
    public function handleRemoteDisk(ActionRequest $request, Action $exportable): mixed
    {
        $temporaryFilePath = config('excel.temporary_files.remote_prefix') . 'laravel-excel-' . Str::random(32) . '.' . $this->getDefaultExtension();
        $isStored          = Excel::store($exportable, $temporaryFilePath, config('excel.temporary_files.remote_disk'), $this->getWriterType());

        if (!$isStored) {
            return \is_callable($this->onFailure)
                ? ($this->onFailure)($request, null)
                : Action::danger(__('Resource could not be exported.'));
        }

        return \is_callable($this->onSuccess)
            ? ($this->onSuccess)($request, $temporaryFilePath)
            : Action::downloadURL(
                $this->getFilename(),
                $this->getDownloadUrl($temporaryFilePath)
            );
    }

    /**
     * @param  string  $filePath
     * @return string
     */
    protected function getDownloadUrl(string $filePath): string
    {
        return URL::temporarySignedRoute('laravel-nova-excel.download', now()->addMinutes(1), [
            'path'     => encrypt($filePath),
            'filename' => $this->getFilename(),
        ]);
    }
}
