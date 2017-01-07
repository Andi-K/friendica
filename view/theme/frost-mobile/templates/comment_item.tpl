		<div class="comment-wwedit-wrapper {{$indent}}" id="comment-edit-wrapper-{{$id}}" style="display: block;">
			<form class="comment-edit-form {{$indent}}" id="comment-edit-form-{{$id}}" action="item" method="post" onsubmit="post_comment({{$id}}); return false;">
				<input type="hidden" name="type" value="{{$type}}" />
				<input type="hidden" name="source" value="{{$sourceapp}}" />
				<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
				<input type="hidden" name="parent" value="{{$parent}}" />
				<input type="hidden" name="jsreload" value="{{$jsreload}}" />
				<input type="hidden" name="preview" id="comment-preview-inp-{{$id}}" value="0" />
				<input type="hidden" name="post_id_random" value="{{$rand_num}}" />

					<a class="comment-edit-photo comment-edit-photo-link" id="comment-edit-photo-{{$id}}" href="{{$mylink}}" title="{{$mytitle}}"><img class="my-comment-photo" src="{{$myphoto}}" alt="{{$mytitle}}" title="{{$mytitle}}" /></a>
				<ul class="comment-edit-bb-{{$id}}">
					<li><a class="editicon boldbb shadow"
						style="cursor: pointer;" title="{{$edbold}}"
						data-role="insert-formatting" data-comment="{{$comment}}" data-bbcode="b" data-id="{{$id}}"></a></li>
					<li><a class="editicon italicbb shadow"
						style="cursor: pointer;" title="{{$editalic}}"
						data-role="insert-formatting" data-comment="{{$comment}}" data-bbcode="i" data-id="{{$id}}"></a></li>
					<li><a class="editicon underlinebb shadow"
						style="cursor: pointer;" title="{{$eduline}}"
						data-role="insert-formatting" data-comment="{{$comment}}" data-bbcode="u" data-id="{{$id}}"></a></li>
					<li><a class="editicon quotebb shadow"
						style="cursor: pointer;" title="{{$edquote}}"
						data-role="insert-formatting" data-comment="{{$comment}}" data-bbcode="quote" data-id="{{$id}}"></a></li>
					<li><a class="editicon codebb shadow"
						style="cursor: pointer;" title="{{$edcode}}"
						data-role="insert-formatting" data-comment="{{$comment}}" data-bbcode="code" data-id="{{$id}}"></a></li>
				</ul>	
				<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text-empty" name="body" onFocus="commentOpen(this,{{$id}});cmtBbOpen({{$id}});" >{{$comment}}</textarea>
				{{if $qcomment}}
					<select id="qcomment-select-{{$id}}" name="qcomment-{{$id}}" class="qcomment" onchange="qCommentInsert(this,{{$id}});">
					<option value=""></option>
				{{foreach $qcomment as $qc}}
					<option value="{{$qc}}">{{$qc}}</option>
				{{/foreach}}
					</select>
				{{/if}}

				<div class="comment-edit-text-end"></div>
				<div class="comment-edit-submit-wrapper" id="comment-edit-submit-wrapper-{{$id}}" style="display: none;">
					<input type="submit" onclick="post_comment({{$id}}); return false;" id="comment-edit-submit-{{$id}}" class="comment-edit-submit" name="submit" value="{{$submit}}" />
				</div>
			</form>
		</div>
