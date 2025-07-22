<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as Permissions;

/**
 * Class Permission
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Permission extends Permissions
{
    public static function getGroupedPermissions()
    {
        return self::all()->groupBy(function ($permission) {
            // Extract the base name (e.g., 'announcements' from 'view_announcements')
            $parts = explode('_', $permission->name);
            $baseName = '';
            if (in_array($parts[0], ['view', 'create', 'update', 'delete', 'delete_any', 'restore', 'export', 'import', 'view_any', 'forcedelete', 'forcedelete_any'])) {
                // For common actions, the base name is everything after the action prefix
                array_shift($parts); // Remove the action prefix (e.g., 'view')
                if (in_array($parts[0], ['any'])) { // Handle 'view_any', 'delete_any', etc.
                    array_shift($parts); // Remove 'any'
                }
                $baseName = implode('_', $parts);
            } else {
                // If no common action prefix, assume the whole name is the base name
                $baseName = $permission->name;
            }

            return Str::title(str_replace('_', ' ', $baseName)); // Convert to readable title case
        })->map(function ($permissions, $groupName) {
            return [
                'name' => $groupName,
                'action' => $permissions->map(function ($permission) {
                    // Extract the action part (e.g., 'view_any' from 'view_any_announcements')
                    $actionName = $permission->name;
                    $parts = explode('_', $actionName);
                    $baseName = '';

                    if (in_array($parts[0], ['view', 'create', 'update', 'delete', 'force_delete', 'restore', 'export', 'import'])) {
                        // For single-word actions
                        if (isset($parts[1]) && $parts[1] === 'any') {
                            // Handle 'view_any', 'delete_any', etc.
                            $actionPrefix = $parts[0] . '_any';
                        } else {
                            $actionPrefix = $parts[0];
                        }
                    } else {
                        $actionPrefix = $actionName; // Default if no specific action prefix found
                    }


                    return [
                        'id' => $permission->id,
                        'name' => str_replace('_', ' ', Str::title($actionPrefix)), // Make action name readable
                        'original_name' => $permission->name, // Keep original for backend processing if needed
                    ];
                })->values()->all(), // Use all() to convert collection to array and values() to re-index
            ];
        })->values()->all(); // Convert final collection to array and re-index
    }
}
