{if count($configRows) eq 0}
    <h1>VPN Access information</h1>
    <table class="table">
        <thead>
        <tr></tr>
        </thead>
        <tbody>
        <tr>
            <td style="text-align: left">Remote ID (IKEv2):</td>
            <td style="text-align: left">Same as Server Address</td>
        </tr>
        <tr>
            <td style="text-align: left">Local ID (IKEv2):</td>
            <td style="text-align: left">{$username}</td>
        </tr>
        <tr>
            <td style="text-align: left">Pre-Shared Key (L2TP):</td>
            <td style="text-align: left">321inter</td>
        </tr>
        </tbody>
    </table>

    <h1>Server List</h1>
    <table class="table">
        <thead>
        <tr>
            <th scope="col" style="text-align:center"></th>
            <th scope="col" style="text-align:left">Server Address</th>
            <th scope="col" style="text-align:left">Location</th>
            <th scope="col" style="text-align:center">Download Openvpn Config File</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$serversList key=serverId item=server}
            <tr>
                <td><img width="30" style="border: 1px solid #eaeaea;" src="{$server.flag}"></td>
                <td style="text-align: left">{$server.address}</td>
                <td style="text-align: left">{$server.location}</td>
                <td class="action-column">
                    <select class="server-list" data-server-id="{$server.id}">
                        {foreach from=$portsList key=portId item=portName}
                            <option value="{$portId}">{$portName}
                        {/foreach}
                    </select>
                    <a id="download-button-{$server.id}" class="btn btn-sm" href="#" target="_blank">Download</a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
{else}
    <h1>Openvpn Config</h1>
    {foreach from=$configRows item=configRow}
        <p style="text-align: left; margin: 0 0 3px">{$configRow}</p>
    {/foreach}
{/if}
{literal}
    <script>
        $(document).ready(function () {
            $('.server-list').each(function () {
                setDownloadLink($(this));
            });
        });
        $('.server-list').on("change", function () {
            setDownloadLink($(this));
        });
        function setDownloadLink(selectElement) {
            let serverId = selectElement.data('server-id');
            let portId = selectElement.children("option:selected").val();
            $('#download-button-' + serverId)
                .attr(
                    'href', 'https://api.321inter.net/v4/configuration/download?port_id='
                    + portId
                    + '&server_id='
                    + serverId
                );
        }
    </script>
{/literal}

