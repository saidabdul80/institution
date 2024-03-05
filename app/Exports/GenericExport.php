<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;


class GenericExport implements FromCollection, WithHeadings, WithDrawings, WithEvents
{
    protected $model;
    protected $ignoreColumns;
    protected $logoPath;    
    protected $headingName;
    protected $relationships;
    protected $appends;
    protected $collection;

    public function __construct(Model $model, array $ignoreColumns = [], $headingName = '', string $logoPath = null, $relationships = [], $appends = [])
    {
        $this->model = $model;
        $this->ignoreColumns = $ignoreColumns;
        $this->logoPath = $logoPath;        
        $this->headingName = $headingName;
        $this->relationships = $relationships;
        $this->appends = $appends;
    }

    /**
     * Returns a collection of the data to be exported.
     *
     * @return \Illuminate\Support\Collection
     */
  
    public function collection() {
        $collection = $this->getCollections();
        
        
        // No need to convert to array and back to collection multiple times
        
    
        Log::info('Collection is empty',[$collection]);
        // Ensure there's at least one item in the collection for heading row calculation
        if ($collection->isEmpty()) {
            // Handle empty collection case, e.g., return a collection with only headings
            return collect([[$this->headingName]]);
        }
        
        // Simplify the creation of the heading row
        $headingRow = [$this->headingName] + array_fill(1, count($collection->first()) - 1, '');
                   
        return $collection;
    }

    public function getCollections() {
        $query = $this->model::query();

        if (!empty($this->relationships)) {
            $query->with($this->relationships);
        }

        return $query->get()->map(function ($item) {
            if (!empty($this->appends)) {
                $item->setAppends($this->appends);
            }
            return collect($item)->except($this->ignoreColumns)->all();
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestColumn = $sheet->getHighestColumn();
                $headingRange = 'A1:' . $highestColumn . '1';

                // Merge cells for the heading row
                $sheet->mergeCells($headingRange);

                // Center the heading text
                $sheet->getStyle($headingRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Optional: Apply additional styling to the heading
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->setSize(14);
            },
        ];
    }

    /**
     * Determine the headings based on the model's attributes, excluding ignored columns.
     *
     * @return array
     */
    public function headings(): array
    {
        
        $collection =$this->getCollections();      
        Log::info('Debug coll:', array_keys($collection->first()));      
        return array_keys($collection->first());
    }

    public function drawings()
    {
        if ($this->logoPath && file_exists($this->logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath($this->logoPath);
            $drawing->setHeight(50);
            $drawing->setCoordinates('A1');
            return [$drawing];
        }

        return [];
    }

}
