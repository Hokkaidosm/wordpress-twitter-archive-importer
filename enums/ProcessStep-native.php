<?php
namespace TwitterArchiveImporterEnums;

use Mabe\Enum\Cl\EnumBc;

enum ProcessStep
{
    use EnumBc;
    
    /** ファイル選択 */
    case SelectFile;
    /** インポート */
    case Imported;
    /** ファイル削除 */
    case Deleted;
}