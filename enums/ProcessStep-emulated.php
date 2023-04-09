<?php
namespace TwitterArchiveImporterEnums;

use Mabe\Enum\Cl\EmulatedUnitEnum;

final class ProcessStep extends EmulatedUnitEnum {
    /** ファイル選択 */
    protected const SelectFile = null;
    /** インポート */
    protected const Imported = null;
    /** ファイル削除 */
    protected const Deleted = null;
}