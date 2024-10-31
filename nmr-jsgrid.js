(function ($) {
    function showError(jqXHR, textStatus) {
        var message = textStatus;
        if (jqXHR.responseJSON && jqXHR.responseJSON.data) {
            message = jqXHR.responseJSON.data;
        }
        alert("Error: " + message);
    }

    $(document).ready(function () {
        (function () {
            const ids = $.map($('[data-nmrtype="jsgrid"]'), (val, i) => val.id);
            if (!ids || ids.length < 1) {
                return;
            }

            var SimpleJsonField = function (config) {
                jsGrid.Field.call(this, config);
            };
            SimpleJsonField.prototype = new jsGrid.Field({

                css: "simple-json-field",
                align: "center",


                sorter: function (s1, s2) {
                    return s1 > s2;
                },

                itemTemplate: function (value) {
                    if (!value) {
                        return value;
                    }
                    var result = '';
                    try {
                        var tmp = JSON.parse(value);
                        if (!Array.isArray(tmp)) {
                            result = 'Error - JSON should be an array of objects';
                        } else {
                            result = `${tmp.length} columns`;
                        }
                    } catch (error) {
                        result = 'Invalid JSON';
                    }
                    return result;
                },

                insertTemplate: function (value) {
                    this._insertPicker = $("<textarea>");
                    return this._insertPicker;
                },

                editTemplate: function (value) {
                    this._editPicker = $("<textarea>");
                    this._editPicker.val(value);
                    return this._editPicker;
                },

                insertValue: function () {
                    return this._insertPicker.val();
                },

                editValue: function () {
                    return this._editPicker.val();
                }
            });

            jsGrid.fields.simpleJson = SimpleJsonField;

            function on_config_read(data) {
                if (!data || !Array.isArray(data)) {
                    return;
                }
                data.forEach(element => {
                    const cfg = element.config;
                    const args = `${element.action}`;

                    cfg.controller = {
                        loadData: function (filter) {
                            return $.ajax({
                                type: "GET",
                                url: `${element.url}?action=${args}`,
                                data: filter
                            });
                        },
                        updateItem: function (item) {
                            return $.ajax({
                                type: "PUT",
                                url: `${element.url}?action=${args}`,
                                data: item
                            });
                        },
                        insertItem: function (item) {
                            return $.ajax({
                                type: "POST",
                                url: `${element.url}?action=${args}`,
                                data: item
                            });
                        },
                        deleteItem: function (item) {
                            return $.ajax({
                                type: "DELETE",
                                url: `${element.url}?action=${args}`,
                                data: {
                                    "id": item.id
                                }
                            });
                        },
                    }
                    $(`#${element.name}`).jsGrid(cfg);
                });

            }
            $.get(`${nmrapi.url}`, {
                    'ids': ids
                }, on_config_read)
                .fail(function (jqXHR, textStatus) {
                    showError(jqXHR, textStatus);
                });
        })();

    });
})(jQuery);