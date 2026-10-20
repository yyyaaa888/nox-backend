<?php

test('应用能正常返回响应', function () {
    $response = $this->get('/');

    $response->assertStatus(403);
});
