(function () {

    var editor = null;

    var domUtils = UM.dom.domUtils;

    // 右键菜单HTML模板
    var tableContextMenuTpl = '<div class="edui-table-context-menu hidden">' +
        '<div class="edui-table-menu-item" data-action="insert-row-before">在上方插入行</div>' +
        '<div class="edui-table-menu-item" data-action="insert-row-after">在下方插入行</div>' +
        '<div class="edui-table-menu-item" data-action="delete-row">删除行</div>' +
        '<div class="edui-table-menu-item" data-action="insert-col-before">在左侧插入列</div>' +
        '<div class="edui-table-menu-item" data-action="insert-col-after">在右侧插入列</div>' +
        '<div class="edui-table-menu-item" data-action="delete-col">删除列</div>' +
        '<div class="edui-table-menu-item" data-action="merge-cell">合并单元格</div>' +
        '<div class="edui-table-menu-item" data-action="split-cell">拆分单元格</div>' +
        '</div>';

    var TableEvent = {

        // 在指定位置插入行
        insertRow: function (table, td, position) {
            var tr = domUtils.findParentByTagName(td, 'tr', true);
            var newTr = tr.cloneNode(true);
            // 清空所有单元格内容
            var cells = newTr.cells;
            for (var i = 0; i < cells.length; i++) {
                cells[i].innerHTML = '&nbsp;';
                // 清除可能存在的rowspan和colspan
                cells[i].removeAttribute('rowspan');
                cells[i].removeAttribute('colspan');
            }

            if (position === 'before') {
                tr.parentNode.insertBefore(newTr, tr);
            } else {
                if (tr.nextSibling) {
                    tr.parentNode.insertBefore(newTr, tr.nextSibling);
                } else {
                    tr.parentNode.appendChild(newTr);
                }
            }
        },

        // 删除行
        deleteRow: function (table, td) {
            var tr = domUtils.findParentByTagName(td, 'tr', true);
            var rowIndex = tr.rowIndex;

            // 检查表格是否只有一行
            if (table.rows.length <= 1) {
                // 如果只有一行，删除整个表格
                domUtils.remove(table);
                return;
            }

            // 处理合并单元格的情况
            for (var i = 0; i < tr.cells.length; i++) {
                var cell = tr.cells[i];
                if (cell.rowSpan > 1) {
                    // 如果有跨行，需要在下一行添加单元格
                    var nextRow = table.rows[rowIndex + 1];
                    var newCell = nextRow.insertCell(i);
                    newCell.innerHTML = '&nbsp;';
                    if (cell.colSpan > 1) {
                        newCell.colSpan = cell.colSpan;
                    }
                }
            }

            tr.parentNode.removeChild(tr);
        },

        // 在指定位置插入列
        insertCol: function (table, td, position) {
            var cellIndex = td.cellIndex;
            var rows = table.rows;

            for (var i = 0; i < rows.length; i++) {
                var tr = rows[i];
                var refCell = null;

                // 查找参考单元格
                for (var j = 0; j < tr.cells.length; j++) {
                    var cell = tr.cells[j];
                    var cellStart = j;
                    var cellEnd = j + (cell.colSpan || 1) - 1;

                    if (cellIndex >= cellStart && cellIndex <= cellEnd) {
                        refCell = cell;
                        break;
                    }
                }

                if (!refCell) continue;

                // 如果参考单元格有colspan，增加colspan值
                if (refCell.colSpan > 1) {
                    refCell.colSpan += 1;
                    continue;
                }

                // 创建新单元格
                var newCell = tr.insertCell(position === 'before' ? refCell.cellIndex : refCell.cellIndex + 1);
                newCell.innerHTML = '&nbsp;';

                // 如果参考单元格有rowspan，新单元格也应该有相同的rowspan
                if (refCell.rowSpan > 1) {
                    newCell.rowSpan = refCell.rowSpan;
                    // 跳过被rowspan覆盖的行
                    i += refCell.rowSpan - 1;
                }
            }
        },

        // 删除列
        deleteCol: function (table, td) {
            var cellIndex = td.cellIndex;
            var rows = table.rows;

            // 检查表格是否只有一列
            var firstRow = rows[0];
            var totalColSpan = 0;
            for (var i = 0; i < firstRow.cells.length; i++) {
                totalColSpan += firstRow.cells[i].colSpan || 1;
            }

            if (totalColSpan <= 1) {
                // 如果只有一列，删除整个表格
                domUtils.remove(table);
                return;
            }

            for (var i = 0; i < rows.length; i++) {
                var tr = rows[i];
                var deleteIndex = -1;

                // 查找要删除的单元格
                for (var j = 0, colIndex = 0; j < tr.cells.length; j++) {
                    var cell = tr.cells[j];
                    var colSpan = cell.colSpan || 1;

                    if (colIndex <= cellIndex && cellIndex < colIndex + colSpan) {
                        // 找到包含目标列的单元格
                        if (colSpan > 1) {
                            // 如果有colspan，减少colspan值
                            cell.colSpan -= 1;
                            // 不需要物理删除单元格
                            break;
                        } else {
                            // 记录要删除的单元格索引
                            deleteIndex = j;
                            break;
                        }
                    }

                    colIndex += colSpan;
                }

                // 删除单元格
                if (deleteIndex !== -1) {
                    tr.deleteCell(deleteIndex);
                }

                // 处理rowspan，跳过被rowspan覆盖的行
                for (var j = 0; j < tr.cells.length; j++) {
                    var rowSpan = tr.cells[j].rowSpan || 1;
                    if (rowSpan > 1) {
                        i += rowSpan - 1;
                        break;
                    }
                }
            }
        },

        // 合并单元格
        mergeCell: function (table, td) {
            var editor = this;
            // 获取当前选区
            var range = editor.selection.getRange();
            var start = domUtils.findParentByTagName(range.startContainer, ['td', 'th'], true);
            var end = domUtils.findParentByTagName(range.endContainer, ['td', 'th'], true);

            // 如果没有选择多个单元格，提示用户
            if (start === end) {
                alert('请选择多个单元格进行合并');
                return;
            }

            // 获取选区的行列范围
            var startRow = domUtils.findParentByTagName(start, 'tr', true).rowIndex;
            var endRow = domUtils.findParentByTagName(end, 'tr', true).rowIndex;
            var startCol = start.cellIndex;
            var endCol = end.cellIndex;

            // 确保startRow <= endRow 且 startCol <= endCol
            if (startRow > endRow) {
                var temp = startRow;
                startRow = endRow;
                endRow = temp;
            }

            if (startCol > endCol) {
                var temp = startCol;
                startCol = endCol;
                endCol = temp;
            }

            // 检查选区是否是矩形区域
            var isRectangle = true;
            for (var i = startRow; i <= endRow; i++) {
                var row = table.rows[i];
                for (var j = startCol; j <= endCol; j++) {
                    var cell = row.cells[j];
                    if (!cell || cell.rowSpan > 1 || cell.colSpan > 1) {
                        isRectangle = false;
                        break;
                    }
                }
                if (!isRectangle) break;
            }

            if (!isRectangle) {
                alert('无法合并非矩形区域的单元格');
                return;
            }

            // 收集所有单元格的内容
            var contents = [];
            for (var i = startRow; i <= endRow; i++) {
                var row = table.rows[i];
                for (var j = (i == startRow ? startCol : 0); j <= (i == startRow ? endCol : endCol); j++) {
                    var cellIndex = j;
                    if (i != startRow) {
                        cellIndex = j < startCol ? j : startCol;
                    }
                    var cell = row.cells[cellIndex];
                    if (cell && cell.innerHTML && cell.innerHTML.trim() !== '' && cell.innerHTML !== '&nbsp;') {
                        contents.push(cell.innerHTML.trim());
                    }
                }
            }

            // 合并单元格
            var targetCell = table.rows[startRow].cells[startCol];
            targetCell.rowSpan = endRow - startRow + 1;
            targetCell.colSpan = endCol - startCol + 1;

            // 如果有收集到内容，则填充到目标单元格
            if (contents.length > 0) {
                targetCell.innerHTML = contents.join('&nbsp;');
            }

            // 删除其他单元格
            for (var i = startRow; i <= endRow; i++) {
                var row = table.rows[i];
                for (var j = (i == startRow ? startCol + 1 : startCol); j <= endCol; j++) {
                    row.deleteCell(i == startRow ? j : startCol);
                    j--; // 因为删除了一个单元格，索引需要调整
                    endCol--; // 列数也相应减少
                }
            }
        },

        // 拆分单元格
        splitCell: function (table, td) {
            var rowSpan = td.rowSpan || 1;
            var colSpan = td.colSpan || 1;

            // 如果单元格没有合并，提示用户
            if (rowSpan === 1 && colSpan === 1) {
                alert('该单元格未合并，无法拆分');
                return;
            }

            var tr = domUtils.findParentByTagName(td, 'tr', true);
            var rowIndex = tr.rowIndex;
            var cellIndex = td.cellIndex;

            // 拆分单元格
            td.rowSpan = 1;
            td.colSpan = 1;

            // 处理行拆分
            for (var i = 1; i < rowSpan; i++) {
                var newTr = table.rows[rowIndex + i];
                var newCellIndex = 0;

                // 计算新单元格的插入位置
                for (var j = 0; j < cellIndex; j++) {
                    newCellIndex += table.rows[rowIndex].cells[j].colSpan || 1;
                }

                // 在下方行插入新单元格
                for (var j = 0; j < colSpan; j++) {
                    var newCell = newTr.insertCell(newCellIndex + j);
                    newCell.innerHTML = '&nbsp;';
                }
            }

            // 处理列拆分
            for (var i = 1; i < colSpan; i++) {
                var newCell = tr.insertCell(cellIndex + i);
                newCell.innerHTML = '&nbsp;';
            }
        },
    };

    UM.registerWidget('table', {

        tpl:
            "<div class=\"edui-table-wrapper\">" +
            "<div class=\"edui-table-panel\">" +
            "<div class=\"edui-table-create\">" +
            "<div class=\"edui-table-dimension\">" +
            "<div class=\"edui-table-dimension-info\"><span id=\"edui-table-dimension-preview\">0x0</span> <%=lang_table%></div>" +
            "<div class=\"edui-table-dimension-select\" id=\"edui-table-dimension-select\"></div>" +
            "</div>" +
            "</div>" +
            "</div>" +
            "</div>",

        initContent: function (_editor, $widget) {

            var me = this,
                lang = _editor.getLang('table').static,
                tableUrl = UMEDITOR_CONFIG.UMEDITOR_HOME_URL + 'dialogs/table/',
                options = $.extend({}, lang, {'table_url': tableUrl}),
                $root = me.root();

            if (me.inited) {
                $(".edui-table-cell").removeClass("edui-table-cell-selected");
                me.preventDefault();
                return;
            }
            me.inited = true;

            editor = _editor;
            me.$widget = $widget;

            $root.html($.parseTmpl(me.tpl, options));

            /* 初始化表格创建区域 */
            me.initCreateTable();
        },
        initEvent: function () {
            var me = this;

            //防止点击过后关闭popup
            me.root().on('click', function (e) {
                return false;
            });

            // 创建表格区域事件
            me.initCreateTableEvent();

        },
        initCreateTable: function () {
            var me = this,
                $root = me.root(),
                $dimensionSelect = $root.find('#edui-table-dimension-select'),
                $dimensionPreview = $root.find('#edui-table-dimension-preview');

            // 创建10x10的表格选择区域
            var html = '';
            for (var i = 0; i < 10; i++) {
                for (var j = 0; j < 10; j++) {
                    html += '<a href="javascript:void(0)" class="edui-table-cell" data-row="' + (i + 1) + '" data-col="' + (j + 1) + '"></a>';
                }
            }
            $dimensionSelect.html(html);
        },
        initCreateTableEvent: function () {
            var me = this,
                $root = me.root(),
                $dimensionSelect = $root.find('#edui-table-dimension-select'),
                $dimensionPreview = $root.find('#edui-table-dimension-preview');

            // 鼠标移动预览表格尺寸
            $dimensionSelect.delegate('.edui-table-cell', 'mouseover', function (evt) {
                var $cell = $(this),
                    row = $cell.attr('data-row'),
                    col = $cell.attr('data-col');

                $dimensionPreview.text(row + 'x' + col);

                // 高亮显示选中的单元格
                $dimensionSelect.find('.edui-table-cell').each(function () {
                    var $this = $(this),
                        thisRow = parseInt($this.attr('data-row')),
                        thisCol = parseInt($this.attr('data-col'));

                    if (thisRow <= row && thisCol <= col) {
                        $this.addClass('edui-table-cell-selected');
                    } else {
                        $this.removeClass('edui-table-cell-selected');
                    }
                });
            });

            // 点击创建表格
            $dimensionSelect.delegate('.edui-table-cell', 'click', function (evt) {
                var $cell = $(this),
                    row = parseInt($cell.attr('data-row')),
                    col = parseInt($cell.attr('data-col')),
                    border = parseInt($root.find('#edui-table-border').val()) || 1;

                me.insertTable(row, col, border);
                me.$widget.edui().hide();
                return false;
            });
        },

        insertTable: function (row, col, border) {
            var html = '<table border="' + border + '" cellpadding="0" cellspacing="0" width="100%">';
            for (var i = 0; i < row; i++) {
                html += '<tr>';
                for (var j = 0; j < col; j++) {
                    html += '<td>&nbsp;</td>';
                }
                html += '</tr>';
            }
            html += '</table>';

            editor.execCommand('inserttable', html);
        },
        width: 300,
        height: 400,
        initBodyEvent: function () {
            var editor = this;
            var $doc = $(this.document);
            // 移除已存在的菜单
            $doc.find('#edui-table-context-menu').remove();
            // 添加菜单到body
            $doc.find('head').append("<link type=\"text/css\" rel=\"stylesheet\" href=\"" + UMEDITOR_CONFIG.UMEDITOR_HOME_URL + 'dialogs/table/' + "table.css\">");
            $doc.find('body').append(tableContextMenuTpl);

            var $menu = $doc.find('.edui-table-context-menu');
            $doc.on("contextmenu", "table", function (e) {
                e.preventDefault();
                $menu.css({left: e.pageX, top: e.pageY}).removeClass("hidden");
                // 记录当前表格元素
                $menu.data('targetTable', this);
                // 设置当前选中的单元格
                var td = domUtils.findParentByTagName(editor.selection.getStart(), 'td', true) ||
                    domUtils.findParentByTagName(editor.selection.getStart(), 'th', true);
                $menu.data('targetTd', td);
            });

            // 点击菜单项
            $menu.on('click', '.edui-table-menu-item', function () {
                var action = $(this).data('action');
                var table = $menu.data('targetTable');
                var td = $menu.data('targetTd');

                if (!td) {
                    editor.fireEvent('selectionchange');
                    $menu.addClass("hidden");
                    return;
                }

                switch (action) {
                    case 'insert-row-before':
                        TableEvent.insertRow.call(editor, table, td, 'before');
                        break;
                    case 'insert-row-after':
                        TableEvent.insertRow.call(editor, table, td, 'after');
                        break;
                    case 'delete-row':
                        TableEvent.deleteRow.call(editor, table, td);
                        break;
                    case 'insert-col-before':
                        TableEvent.insertCol.call(editor, table, td, 'before');
                        break;
                    case 'insert-col-after':
                        TableEvent.insertCol.call(editor, table, td, 'after');
                        break;
                    case 'delete-col':
                        TableEvent.deleteCol.call(editor, table, td);
                        break;
                    case 'merge-cell':
                        TableEvent.mergeCell.call(editor, table, td);
                        break;
                    case 'split-cell':
                        TableEvent.splitCell.call(editor, table, td);
                        break;
                }

                editor.fireEvent('selectionchange');
                $menu.addClass("hidden");
            });

            // 点击其它区域隐藏菜单
            $doc.on('click', function () {
                $menu.addClass("hidden");
            });
        }
    });


    // 注册表格插入命令
    UM.commands['inserttable'] = {
        execCommand: function (cmd, html) {
            var me = this,
                range = me.selection.getRange(),
                start = range.startContainer,
                td = domUtils.findParentByTagName(start, 'td', true) || domUtils.findParentByTagName(start, 'th', true),
                table;

            if (td && (table = domUtils.findParentByTagName(td, 'table')))
                return;

            var div = me.document.createElement("div");
            div.innerHTML = html;
            table = div.firstChild;

            me.execCommand("inserthtml", table.outerHTML);

            return table;
        }
    };

})();