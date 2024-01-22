<script>
    function getProcess() {
        $.getJSON("/_sys/dashboard/_ajax?<?= @$process_to_find ?>", function(data) {
            console.log(data);
            let cmd_list = [];
            Object.keys(data).forEach(function(key) {
                let $table = $(`table#${key} tbody`);
                $table.html('');
                // general vm
                if (key === 'vm') {
                    let item = data[key];
                    let row = '';
                    row += '<tr>';
                    row += `<td>${item['cpu']}</td>`;
                    row += `<td>${item['ram']}</td>`;
                    row += `<td>${item['disk']}</td>`;
                    row += `<td>${item['uptime']}</td>`;
                    row += '</tr>';
                    $table.append(row);
                }
                // specific config process
                else {
                    for (let i = 0; i < data[key].length; i++) {
                        let item = data[key][i];
                        let row = '';
                        if (!item['cmd'].includes('grep')) { // ignore current cmd (ps, aux, etc)
                            let opacity = 1;
                            let user_css = '';
                            let css_btn = '';
                            if (item['user'] !== 'www-data') {
                                //opacity = .7;
                                user_css = 'warning';
                                css_btn = 'display:none';
                            }
                            row += `<tr style="opacity:${opacity}">`;
                            row += `<td><a style='${css_btn}' href='/_sys/dashboard/_action?action=kill&pid=${item['pid']}' class='red'><i class="fa-solid fa-ban"></i></a></td>`;
                            row += `<td class='text-${user_css}'>${item['user']}</td>`;
                            row += `<td>${item['pid']}</td>`;
                            row += `<td>${item['cpu']}</td>`;
                            row += `<td>${item['ram']}</td>`;
                            row += `<td>${item['start']}</td>`;
                            row += `<td class='green'>${item['cmd']}</td>`;
                            row += '</tr>';
                            $table.append(row);
                            cmd_list.push(item['cmd'])
                        }
                    }
                }
                findJobAndHide(cmd_list);
            });
            setTimeout(function() {
                getProcess();
            }, 1000);
        });
    }
    getProcess();

    function findJobAndHide(cmd_list) {
        $('table#job_config td.fn').each(function(i) {
            let fn = $(this).text(); // Este é o comando pequeno
            let isRunning = cmd_list.some(cmd => cmd.includes(fn)); // Verifica se algum comando grande contém o comando pequeno
            let $row = $(this).closest('tr');
            //console.log(fn,isRunning);
            if (isRunning) {
                $row.css('opacity', 0.5);
                $row.find('.stopArea').show();
                $row.find('.playArea').hide();
            } else {
                $row.css('opacity', 1);
                $row.find('.stopArea').hide();
                $row.find('.playArea').show();
            }
        });
    }
</script>