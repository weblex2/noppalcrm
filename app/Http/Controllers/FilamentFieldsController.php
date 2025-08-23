<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\TableFields;
use App\Models\FilamentConfig;
use App\Filament\Resources\CustomerResource;
use Illuminate\Http\Request;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\DateTimeColumn;
use App\Models\Section;
use App\Models\FilamentAction;
use App\Models\ResourceConfig;
use Filament\Tables\Actions;
use Filament\Forms\Components\Actions\Action;

class FilamentFieldsController extends Controller
{
    //use InteractsWithForms;

    public $form;
    private $userId;
    private $tableName;
    private $field;
    private $isForm;
    private $config;
    public  $fields;
    private $section_ids= [];


    function __construct($tableName='', $form=0){
        $this->tableName = $tableName;
        $this->userId  = Auth::id();
        $this->isForm = $form;
    }

    public function index($table=''){
        $table = DB::select('DESCRIBE fil_customers');;
        $table = json_decode(json_encode($table), true);
        $user_id = $this->userId;
        return view('filament.managefields', compact('table','user_id'));
    }

    public function getSchema(){
        $tableFields = TableFields::where('table', '=', $this->tableName)
            ->where('form', '=', $this->isForm)
            ->orderBy('order', 'ASC')
            ->get();

        // Eindeutige Gruppen (Groups) ermitteln
        $groupIds = $tableFields->pluck('group')->unique();
        $isWizard = ResourceConfig::where('resource', $this->tableName)->value('is_wizard');
        // Struktur aufbauen
        $groups = [];

        foreach ($groupIds as $groupId) {
            // Felder für die aktuelle Gruppe filtern
            $groupFields = $tableFields->where('group', $groupId);

            // Eindeutige Abschnitte (Sections) für die aktuelle Gruppe ermitteln
            $sectionIds = $groupFields->pluck('section')->unique()->sort();

            $sections = [];
            foreach ($sectionIds as  $sectionId) {

                $sectionFields = $groupFields->where('section', $sectionId);

                // 🔍 Hole das passende Label aus der sections-Tabelle
                $sectionLabel = Section::where('resource', $this->tableName)
                    ->where('num', $sectionId)
                    ->value('label') ?? ('Section ' . $sectionId);
                $section_config = FilamentConfig::where('resource', $this->tableName)
                    ->where('section_nr', $sectionId)->first();
                $fieldSchemas = $sectionFields->map(function ($field) {
                    return $this->getField($field);
                })->toArray();

                // Felder für den aktuellen Abschnitt filtern
                $sectionFields = $groupFields->where('section', $sectionId);
                $sectionConfig = FilamentConfig::where('resource',$this->tableName)->where('type','section')->get();
                $sectionLabel  = $section_config->section_name ?? "Section ".$sectionId;


                $fieldSchemas = $sectionFields->map(function ($field) {
                    return $this->getField($field);
                })->toArray();

                // Abschnitt erstellen
                if ($isWizard){

                    //$sectionFields = $groupFields->where('section', $sectionId);
                    $modalFunctionName = FilamentController::getModelFunctionName($section_config->repeats_resource);
                    if (!isset($section_config) || $section_config->is_repeater !== 1) {
                        $sections[] = Forms\Components\Wizard\Step::make($sectionLabel)
                            ->schema($fieldSchemas)
                            ->columns(3);
                    } else {
                        $sections[] = Forms\Components\Wizard\Step::make($sectionLabel)
                            ->schema([
                                Forms\Components\Repeater::make($modalFunctionName)
                                    ->relationship()
                                    ->schema($fieldSchemas)
                                    ->columns(3)
                            ]);
                    }
                }
                else{
                $sections[] = Forms\Components\Section::make($sectionLabel)
                    ->schema($fieldSchemas)
                    ->columns(3) // Optional: Anzahl der Spalten
                    ->collapsible();
                }
            }


            if ($isWizard){
                $groups[] = Forms\Components\Wizard::make()
                ->schema($sections)
                ->columnSpan('full'); // Optional: Vollständige Breite
            }
            else{
            // Gruppe erstellen
            $groups[] = Forms\Components\Group::make()
                ->schema($sections)
                ->columnSpan('full'); // Optional: Vollständige Breite
            }
        }
        //dd($groups);
        return $groups;
    }

    public function getTableFields(){
        $fields = [];
        $tableFields = TableFields::where('table',"=", $this->tableName)
                                     ->where('form',"=",$this->isForm)
                                     ->orderBy('order', 'ASC')
                                     ->get();

        foreach ($tableFields as $index => $tableField ){
            $fields[] = $this->getField($tableField);
        }
        return $fields;
    }

    public function getField($tableField){


            $this->config = $tableField;
            // Create Form Fields
            if ($this->isForm==1){
                switch ($tableField->type){
                    case "text": {
                        $this->field = Forms\Components\TextInput::make($tableField->field);
                        break;
                    }
                    case "select": {
                        $this->field = Forms\Components\Select::make($tableField->field);
                        $this->getSelectOptions();
                        break;
                    }
                    case "date": {
                        $this->field = Forms\Components\DatePicker::make($tableField->field);
                        break;
                    }
                    case "datetime": {
                        $this->field = Forms\Components\DateTimePicker::make($tableField->field);
                        break;
                    }
                    case "toggle": {
                        $this->field = Forms\Components\Toggle::make($tableField->field);
                        break;
                    }
                    case "badge": {
                        $this->field = Forms\Components\TextInput::make($tableField->field);
                        break;
                    }
                    case "link": {
                        $this->field = Forms\Components\TextInput::make($tableField->field);
                        break;
                    }
                    case "markdown": {
                        $this->field = Forms\Components\MarkdownEditor::make($tableField->field);
                        break;
                    }

                    case "relation": {
                        #use Filament\Forms\Components\BelongsToSelect;
                        $this->field = Forms\Components\Select::make($tableField->field);
                        $this->setRelationship();
                        break;
                    }


                    default: {
                        $this->field = Forms\Components\TextInput::make($tableField->field);
                        break;
                    }

                }
                $this->setLabel();
                $this->setRequired();
                $this->setColspan();
                $this->format();
                $this->setVisible();
                $this->setDisabled();
                $this->setBgColor();
            }
            // Create View Fields
            else{
                switch ($tableField->type){
                    case "text": {
                        $this->field = Tables\Columns\TextColumn::make($tableField->field);
                        break;
                    }
                    case "date": {
                        $this->field = Tables\Columns\TextColumn::make($tableField->field);
                        $this->formatDate();
                        break;
                    }
                    case "datetime": {
                        $this->field = Tables\Columns\TextColumn::make($tableField->field);
                        $this->formatDate();
                        break;
                    }
                    case "toggle": {
                        $this->field = Tables\Columns\IconColumn::make($tableField->field);
                        $this->setBoolean();
                        break;
                    }
                    case "badge": {
                        $this->field = Tables\Columns\TextColumn::make($tableField->field);
                        $this->field->badge();
                        break;
                    }
                    case "select": {
                        $this->field = Tables\Columns\TextColumn::make($tableField->field);
                        $this->getSelected();
                        break;
                    }
                    case "link": {
                        $this->field = Tables\Columns\TextColumn::make($tableField->field);
                        $this->setLink();
                        break;
                    }

                    case "number" :{
                        $this->field = Tables\Columns\TextColumn::make($tableField->field);
                        $this->setNumeric();
                        break;
                    }

                    case "relation": {
                        $fieldname = $this->config->relation_table ."." .$this->config->relation_show_field;
                        $this->field = Tables\Columns\TextColumn::make($fieldname);
                        break;
                    }

                    default: {
                         $this->field = Tables\Columns\TextColumn::make($tableField->field);
                        break;
                    }

                }
                $this->setLabel();
                $this->setIcon();
                $this->format();
                $this->setColor();
                $this->extraAttributes();
                $this->setVisible();
                $this->setBadgeColor();
                $this->align();
                $this->setSearchable();
                $this->setToggable();
                $this->setBadge();
                $this->setBgColor();
                $this->setSortable();
            }

            return  $this->field;

    }

    private function getSelectOptions(){
        $options = $this->config->select_options;
        if ($options!=""){
            list($table, $filter) = explode('.',$options);
            $options = FilamentConfig::where('resource', $table)
                    ->where('field', $filter)
                    ->orderBy('order')
                    ->pluck('value', 'key')
                    ->toArray();
            $this->field->options($options ?? []);
        }
    }




    private function setBadge(){
        if ($this->config->is_badge){
            $this->field->badge();
        }
    }

    private function setLabel(){
        if ($this->config->label!=""){
            $this->field->label($this->config->label);
        }
        else{
           $this->field->label($this->config->field);
        }
    }

    private function setBoolean(){
        $this->field->boolean();
    }

    private function setRequired(){
        if ($this->config->required){
            $this->field->required();
        }
    }
    private function setDisabled(){
        if ($this->config->disabled){
            $this->field->disabled();
        }
    }
    private function setSearchable(){
        if ($this->config->searchable){
            $this->field->searchable();
        }
    }

    private function setSortable(){
        if ($this->config->sortable){
            $this->field->sortable();
        }
    }

    private function setToggable(){
        if ($this->config->is_toggable){
            $this->field->toggleable(isToggledHiddenByDefault: true);
        }
    }

    private function setDate(){
        $this->field->date();
    }

    private function setNumeric(){
        $this->field->numeric();
    }

    private function formatDate(){
        $this->field->formatStateUsing(function ($state) {
            if ($this->config->type=='date'){
                $format = 'd.m.Y';
            }
            elseif ($this->config->type=='datetime'){
            $format = 'd.m.Y H:i:s';
            }
             if (! $state instanceof \DateTimeInterface || $state->format('Y') < 1900) {
            return '-';
            }
            return $state->format($format ?? 'd.m.Y');

        });
    }

    private function align(){
        if ($this->config->align=='r'){
             $this->field->alignRight();
        }
    }

    private function setColspan(){
        if ($this->config->colspan===0) {
            $this->field->columnSpan(1);

        }
        elseif ($this->config->colspan!="") {
            $this->field->columnSpan($this->config->colspan);
        }
        else{
            $this->field->columnSpan(1);
            //$this->field->columnSpanFull();
        }
    }

    private function setIcon(){
        if ($this->config->icon){
            $this->field->icon($this->config->icon);
        }
        if ($this->config->icon_color){
            $this->field->iconColor($this->config->icon_color);
        }
    }

    private function setBgColor(){
        if ($this->config->bgcolor!=""){
            $color = 'background-color: '.$this->config->bgcolor;
            $this->field->extraAttributes(['style' => $color]);
        }
    }

    private function setLink(){
        if ($this->config->link){
            if (substr($this->config->link,0,6)=='return'){
                $function = eval($this->config->link);
                $this->field->url($function);
            }
            else{
                $this->field->url($this->config->link);
            }
        }
        if ($this->config->link_target=='_blank'){
            $this->field->openUrlInNewTab();
        }
    }

    private function setRelationship(){
        $this->field->relationship($this->config->relation_table, $this->config->relation_show_field);
    }

    private function setColor(){
        $this->field->color($this->config->color);
    }

    private function format(){
        if (trim($this->config->format)!=""){
            if (substr($this->config->format,0,6)=='return'){
                $function = eval($this->config->format);
                $this->field->formatStateUsing($function);
            }
        }
    }

    private function extraAttributes(){
        if (trim($this->config->extra_attributes)!=""){
            if (substr($this->config->extra_attributes,0,6)=='return'){
                $function = eval($this->config->extra_attributes);
                $this->field->extraAttributes($function);
            }
        }
    }

    private function setVisible(){
        if (trim($this->config->visible)!=""){
            if (substr($this->config->visible,0,6)=='return'){
                $function = eval($this->config->visible);
                $this->field->visible($function);
            }
            else{
                $this->field->visible($this->config->visible);
            }
        }
    }

    public function getSelected(){
        list($table, $field) = explode('.', $this->config->select_options);
        $options = FilamentConfig::where('resource', $table)
            ->where('field', $field)
            ->orderBy('order')
            ->pluck('value', 'key')
            ->toArray();
        $this->field->formatStateUsing(fn (?string $state) => $options[$state] ?? $state);
    }

    public function setBadgeColor(){
        if (trim($this->config->badge_color)!=""){
            if (substr($this->config->badge_color,0,6)=='return'){
                $function = eval($this->config->badge_color);
                $this->field->colors($function);
            }
            else{
                $this->field->color($this->config->badge_color);
            }
        }
    }

    public static function getActions($type = "header"){
        $db_actions = FilamentAction::where('resource','contact_person')->where('type',$type)->get();
        foreach ($db_actions as $db_action){
            $parameter = json_decode($db_action->parameter, true) ?? [];
            $action = Tables\Actions\Action::make($db_action->action_name);
            $action->label($db_action->label);
            $action->icon('heroicon-o-inbox');
            $action->modalHeading($db_action->label);
            $action->modalContent(fn () => view($db_action->view, $parameter));
            $action->modalSubmitAction($db_action->action_submit_action == 0 ? false : true);
            $action->modalCancelAction($db_action->action_cancel_action == 0 ? false : true);
            $actions[] = $action;
        }

        return $actions;
    }
}
