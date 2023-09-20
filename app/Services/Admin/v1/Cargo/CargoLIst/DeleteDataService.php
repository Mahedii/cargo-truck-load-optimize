<?php

namespace App\Services\Admin\v1\Cargo\CargoList;

use Auth;
use File;
use Illuminate\Http\Request;
use App\Models\Cargo\Cargo;

class DeleteDataService
{
    /**
     * Client form container
     *
     * @var string $slug
     */
    private string $slug;

    /**
     * Set the container
     */
    public function __construct($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get response for deleting data
     *
     * @return array
     */
    public function getResponse(): array
    {
        $deleteTableData = $this->deleteTableData($this->slug);

        if ($deleteTableData) {
            $result = [
                'status' => 200,
                'message' => 'Data "' . $this->slug . '" deleted successfully',
            ];
        } else {
            $result = [
                'status' => 500,
                'message' => 'Data can not be deleted.',
            ];
        }

        return $result;
    }

    /**
     * Delete selected table data
     *
     * @param string $slug
     * @return int
     */
    private function deleteTableData(string $slug): int
    {
        $deleteQuery = Cargo::where('slug', $slug)->delete();

        return $deleteQuery;
    }
}
