<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Edition;
use App\Models\Sector;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('company_edition', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->primary(['company_id', 'edition_id']);
            $table->timestamps();
        });

        Schema::create('company_sector', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained('sectors')->cascadeOnDelete();
            $table->primary(['company_id', 'sector_id']);
            $table->timestamps();
        });

        // If companies has JSON columns with existing data, migrate them into the new tables
        if (Schema::hasColumn('companies', 'editions') || Schema::hasColumn('companies', 'sectors')) {
            $companies = DB::table('companies')->select('id', 'editions', 'sectors')->get();

            foreach ($companies as $company) {
                // Migrate editions
                if (!empty($company->editions)) {
                    $editions = json_decode($company->editions, true);

                    // If json_decode failed but value is a simple string, try to parse as comma separated
                    if ($editions === null && is_string($company->editions)) {
                        $editions = array_map('trim', explode(',', $company->editions));
                    }

                    if (is_array($editions)) {
                        foreach ($editions as $e) {
                            $editionId = null;

                            if (is_numeric($e)) {
                                $editionId = (int) $e;
                                $edition = Edition::find($editionId);
                                if (!$edition) {
                                    // skip non-existing numeric id
                                    continue;
                                }
                            } elseif (is_string($e) && $e !== '') {
                                // find or create by name
                                $edition = Edition::firstOrCreate(['name' => $e]);
                                $editionId = $edition->id;
                            }

                            if ($editionId) {
                                // Insert pivot if not exists
                                $exists = DB::table('company_edition')
                                    ->where('company_id', $company->id)
                                    ->where('edition_id', $editionId)
                                    ->exists();

                                if (!$exists) {
                                    DB::table('company_edition')->insert([
                                        'company_id' => $company->id,
                                        'edition_id' => $editionId,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Migrate sectors
                if (!empty($company->sectors)) {
                    $sectors = json_decode($company->sectors, true);

                    if ($sectors === null && is_string($company->sectors)) {
                        $sectors = array_map('trim', explode(',', $company->sectors));
                    }

                    if (is_array($sectors)) {
                        foreach ($sectors as $s) {
                            $sectorId = null;

                            if (is_numeric($s)) {
                                $sectorId = (int) $s;
                                $sector = Sector::find($sectorId);
                                if (!$sector) {
                                    continue;
                                }
                            } elseif (is_string($s) && $s !== '') {
                                $sector = Sector::firstOrCreate(['name' => $s]);
                                $sectorId = $sector->id;
                            }

                            if ($sectorId) {
                                $exists = DB::table('company_sector')
                                    ->where('company_id', $company->id)
                                    ->where('sector_id', $sectorId)
                                    ->exists();

                                if (!$exists) {
                                    DB::table('company_sector')->insert([
                                        'company_id' => $company->id,
                                        'sector_id' => $sectorId,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Now drop the JSON columns from companies
        if (Schema::hasColumn('companies', 'editions')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('editions');
            });
        }

        if (Schema::hasColumn('companies', 'sectors')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('sectors');
            });
        }
    }

    public function down(): void
    {
        // Add the JSON columns back if they don't exist
        if (!Schema::hasColumn('companies', 'editions') || !Schema::hasColumn('companies', 'sectors')) {
            Schema::table('companies', function (Blueprint $table) {
                if (!Schema::hasColumn('companies', 'editions')) {
                    $table->json('editions')->nullable()->after('visible');
                }

                if (!Schema::hasColumn('companies', 'sectors')) {
                    $table->json('sectors')->nullable()->after('editions');
                }
            });
        }

        Schema::dropIfExists('company_sector');
        Schema::dropIfExists('company_edition');
        Schema::dropIfExists('sectors');
    }
};
